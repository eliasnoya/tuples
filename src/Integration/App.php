<?php

namespace Tuples\Integration;

use Tuples\Container\Container;
use Tuples\Database\{Database, DatabasePool};
use Tuples\Http\{Request, Response, Route, RouteGroup, Router};
use Tuples\Utils\PhpBootstrapper;

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
    /**
     * The purpose of this container is to hold all the project depedencies
     * @var Container
     */
    public Container $container;

    public function __construct(public string $basePath = "./")
    {
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
        (new PhpBootstrapper($this->basePath))->boot();

        // boot container unique instance
        $this->container = Container::instance();

        $this->registerRouter();
    }

    /**
     * Bypass to Container
     * Register SINGLETON dependency to Container
     * The callable/object set in $concrete will be the same instance each time you call it
     *
     * @param string $name
     * @param mixed $concrete
     * @return void
     */
    public function singleton(string $name, mixed $concrete): void
    {
        $this->container->singleton($name, $concrete);
    }

    /**
     * Bypass to Container
     * Register CALLABLE dependency to Container
     * The resolution set in $concrete (\Closure, Object) will generate a instance each time you call it
     *
     * @param string $name
     * @param mixed $concrete
     * @return void
     */
    public function callable(string $abstract, mixed $concrete): void
    {
        $this->container->callable($abstract, $concrete);
    }

    /**
     * Boot DotEnv .env => enviorment variables
     *
     * @return void
     */
    public function useDotEnv()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable($this->basePath);
        $dotenv->load();
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
        $router = $this->container->resolve(Router::class);
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
        $router = $this->container->resolve(Router::class);
        $router->add($route);
    }

    /**
     * Resolve request on router and return response
     *
     * @return Response
     */
    private function resolve(): Response
    {
        $resolver = new Resolver(
            $this->container->resolve(Router::class),
            $this->container->resolve(Request::class),
            $this->container->resolve(Response::class)
        );

        return $resolver->execute();
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

        $this->resolve()->emit();
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

                $roadRunnerPsr7Worker->respond($this->resolve()->psr());

                $this->container->unbind(Request::class);
                $this->container->unbind(Response::class);

                gc_collect_cycles();
            } catch (\Throwable $th) {
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
        $poolExists = $this->container->exists(DatabasePool::class);

        if (!$poolExists) {
            // Pool doesnt exists, create singleton with this as default connection
            $this->container->singleton(DatabasePool::class, function () use ($dsn, $user, $pass, $opts) {
                $pdo = new \PDO($dsn, $user, $pass, $opts);
                $dbDefault = new Database($pdo);
                return new DatabasePool($dbDefault);
            });
        } else {
            // Pool already exists add this connection to it
            /** @var DatabasePool $pool */
            $pool = $this->container->resolve(DatabasePool::class);
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
        $request = new Request($serverRequest);
        // this is a worker-context request, use ephemeral container to share dependencies inside the request lifecycle
        $request->useEphemeralContainer();
        $this->singleton(Request::class, $request);
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
