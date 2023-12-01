<?php

namespace Tuples\Integration;

use Tuples\Container\Container;
use Tuples\Container\Traits\HasContainer;
use Tuples\Database\{Database, DatabasePool};
use Tuples\Exception\Contracts\ExceptionHandler;
use Tuples\Exception\DefaultExceptionHandler;
use Tuples\Http\{Request, Response, Route, RouteGroup, Router};
use Tuples\Utils\PhpBootstrapper;
use Tuples\View\Twig;

/**
 * Integrates configurations, containers, databases, HTTP router, and messaging to implement a lightweight API or web application.
 * Supports two execution modes: not-dying (RoadRunner worker) and dying (standard PHP) context.
 *
 * In terms of implementation, it remains the same; the only difference lies in the last command of the entrypoint:
 * - $app->emit(): Dying context
 * - $app->work(): Not-dying context
 *
 * This lightweight framework is developed with a focus on a not-dying context, just for some speed.
 */
class App
{
    use HasContainer;

    protected bool $workerContext = false;

    public function __construct(public string $basePath = "./")
    {
        $this->bootContainer(Container::instance());

        // Load base_path to ENVIORMENT for further use basePath() & storagePath() helpers
        $_ENV['base_path'] = realpath($basePath);
    }

    public function isWorker()
    {
        $this->workerContext = true;
    }

    /**
     * Standard App bootstraping
     *
     * @return void
     */
    public function defaults()
    {
        // Use DotEnv
        $this->useDotEnv();

        // Setup basic PHP configuration and directory structure
        (new PhpBootstrapper())->boot();

        $this->setViewsPath();

        $this->registerRouter();

        $this->callable(RouteResolver::class, RouteResolver::class);

        // Default Exception Handler
        $this->registerExceptionHandler(DefaultExceptionHandler::class);
    }

    /**
     * Set views root path.
     * sets views_path enviorment variable,
     * used on \Tuples\View\View twig wrapper & view(...) helper
     *
     * @param string|null $viewsPath (absolute path of directory)
     * @return void
     */
    public function setViewsPath(string|null $viewsPath = null): void
    {
        $viewsPath = is_null($viewsPath) ? basePath('/views') : $viewsPath;

        // ensure unix slashs for paths
        $viewsPath = str_replace('\\', '/', $viewsPath);

        if (!file_exists($viewsPath) || !is_dir($viewsPath)) {
            throw new \InvalidArgumentException("path $viewsPath doesnt exists");
        }

        $_ENV['views_path'] = realpath($viewsPath);
    }


    public function registerExceptionHandler(string $handler)
    {
        if (!class_exists($handler) || !is_subclass_of($handler, ExceptionHandler::class)) {
            throw new \Error("Handler does not exist or does not extend \Tuples\Exception\Contracts\ExceptionHandler.");
        }
        // Resolves the "ExceptionHandler" dependency each time it is invoked.
        $this->callable("ExceptionHandler", $handler);
    }

    /**
     * Boot DotEnv .env => enviorment variables
     *
     * @return void
     */
    public function useDotEnv()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(basePath());
        $dotenv->load();
    }

    public function useIf(bool $bool, string $middleware)
    {
        if ($bool) {
            $this->use($middleware);
        }
    }

    /**
     * Add middleware to ROUTER so that he inherits the middlewares to Routes Groups and Routes
     *
     * @param string $middleware
     * @return void
     */
    public function use(string $middleware)
    {
        /** @var Router $router */
        $router = $this->resolve(Router::class);
        $router->setMiddleware($middleware);
    }

    /**
     * shortcut to add a Route from App instance
     *
     * @param Route|RouteGroup $route
     * @return void
     */
    public function route(Route|RouteGroup $route)
    {
        $this->useRoute($route);
    }

    /**
     * shorcut to add an array of Routes from App instance
     *
     * @param array $routes
     * @return void
     */
    public function routes(array $routes)
    {
        $this->useRoute($routes);
    }

    /**
     * Handle the route adding shorcuts: resolves the router dependency,
     * and then add the route/routegroup/array to it
     *
     * @param Route|RouteGroup|array $route
     * @return void
     */
    private function useRoute(Route|RouteGroup|array $route)
    {
        /** @var Router $router */
        $router = $this->resolve(Router::class);
        $router->add($route);
    }

    /**
     * standard-dying execution
     * The script executes the request and then die ⚰️ :(
     *
     * @return void
     */
    public function emit(): void
    {
        $this->registerRequestFromGlobals();
        $this->registerResponse();

        /** @var RouteResolver $resolver */
        $resolver = $this->resolve(RouteResolver::class);
        $resolver->executeFromRequest()->emit();
    }

    /**
     * not-dying execution
     * Waits for Request and transmit the response to the roadrunner http server 😎 :P
     *
     * @return void
     */
    public function work(): void
    {
        $worker = \Spiral\RoadRunner\Worker::create();
        $psrFactory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $roadRunnerPsr7Worker = new \Spiral\RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

        while ($serverRequest = $roadRunnerPsr7Worker->waitRequest()) {
            try {
                if ($serverRequest === null) {
                    break;
                }

                $this->registerRequest($serverRequest);
                $this->registerResponse();

                /** @var RouteResolver $resolver */
                $resolver = $this->resolve(RouteResolver::class);
                $response = $resolver->executeFromRequest()->psr();

                $roadRunnerPsr7Worker->respond($response);

                $this->unbind(Request::class);
                $this->unbind(Response::class);

                gc_collect_cycles();
            } catch (\Throwable $th) {
                // RoadRunner Worker Exception. Never happen, but we are respecting his docs...
                $roadRunnerPsr7Worker->respond(new \Nyholm\Psr7\Response(500, [], $th->getMessage()));
            }
        }
    }

    /**
     * Register and configure the Default database on the database pool
     *
     * @param string $dsn
     * @param string $user
     * @param string|null $pass
     * @param array|null $opts
     * @return void
     */
    public function useDefaultDatabase(string $dsn, string $user, string|null $pass, array|null $opts = null): void
    {
        $this->useDatabase('default', $dsn, $user, $pass, $opts);
    }

    /**
     * Init DatabasePool on container with default PDO needed variables
     *
     * @param string $dsn
     * @param string $user
     * @param string|null $pass
     * @param array|null $opts
     * @return void
     */
    public function useDatabase(string $connName, string $dsn, string $user, string|null $pass, array|null $opts = null): void
    {
        $poolExists = $this->exists(DatabasePool::class);

        if (!$poolExists) {
            // Pool doesnt exists, create singleton with this as default connection
            $this->singleton(DatabasePool::class, function () use ($dsn, $user, $pass, $opts) {
                $pdo = new \PDO($dsn, $user, $pass, $opts);
                $dbDefault = new Database($pdo);
                return new DatabasePool($dbDefault);
            });
        } else {
            // Pool already exists add this connection to it
            /** @var DatabasePool $pool */
            $pool = $this->resolve(DatabasePool::class);
            $pool->add($connName, new Database(new \PDO($dsn, $user, $pass, $opts)));
        }
    }

    /**
     * Register our Http Router depedency
     *
     * @return void
     */
    private function registerRouter()
    {
        $this->singleton(Router::class, new Router());
    }

    /**
     * Register request from a \Psr\Http\Message\ServerRequestInterface ONLY for "not-dying-context impl"
     *
     * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
     * @return void
     */
    private function registerRequest(\Psr\Http\Message\ServerRequestInterface $serverRequest): void
    {
        $this->singleton(Request::class, new Request($serverRequest));
    }

    /**
     * Register request intance ONLY for "standard-dying impl"
     *
     * @return void
     */
    private function registerRequestFromGlobals(): void
    {
        $this->singleton(Request::class, Request::createFromGlobals());
    }

    /**
     * Register response instance
     *
     * @return void
     */
    private function registerResponse(): void
    {
        $this->singleton(Response::class, new Response(new \Nyholm\Psr7\Response()));
    }
}
