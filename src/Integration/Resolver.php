<?php

namespace Tuples\Integration;

use Tuples\Http\Request;
use Tuples\Http\Response;
use Tuples\Http\Router;

/**
 * Class to resolve the Request LifeCycle
 */
class Resolver
{
    public function __construct(protected Router $router, protected Request $request, protected Response $response)
    {
    }

    /**
     * Find route and execute his action
     *
     * @throws \Throwable On action failure or not found route
     * @return Response On Success
     */
    public function execute(): Response
    {
        try {
            /** @var \Tuples\Http\Route $route */
            list($route, $params) = $this->router->lookup($this->request->method(), $this->request->path());
            if (!$route) {
                throw new \Error("404 not found");
            }

            $this->request->setRoute($route);
            $this->request->setRouteParams($params);

            list($controller, $method) = $route->getAction();

            // Register controller on Container as callabale (instance every time its call)
            container()->callable($controller, $controller);

            /************************************************************
            | Define the chain, route action + all middlewares
             ************************************************************/
            // Convert the route action to a \Closure; this will be the last command of the chain
            $next = function () use ($controller, $method, $params) {
                return $this->handle($controller, $method, $params);
            };
            // Loop through middlewares in reverse order
            // and redefine $next as middleware \Closure in the chain
            $middlewares = $route->getMiddlewares();
            for ($i = count($middlewares) - 1; $i >= 0; $i--) {

                $middleware = $middlewares[$i];

                // Register middleware on Container as callabale (instance every time its call)
                container()->callable($middleware, $middleware);

                $next = function () use ($middleware, $next) {
                    return $this->handle($middleware, 'handle', ['next' => $next]);
                };
            }

            // Execute the chain
            return $next();
        } catch (\Throwable $th) {

            return $this->response->isJson()->body([
                'message' => $th->getMessage(),
                'trace' => $th->getTrace(),
            ]);
        }
    }

    /**
     * Execute the chain method and return \Closure or \Tuples\Http\Response (cast result on response object if is needed)
     *
     * @param string $callback
     * @param string $method
     * @param array $args
     * @return Response|\Closure
     */
    private function handle(string $depedency, string $method, array $args): Response|\Closure
    {
        $value = container()->resolveAndExecute($depedency, $method, $args);

        // If it is a \Closure (next() function in the chain)
        // or if it is already a response, return it unmodified
        if ($value instanceof \Closure || $value instanceof Response) {
            return $value;
        }

        // Otherwise, return the response instance with the result as the body
        return $this->response->body($value);
    }
}
