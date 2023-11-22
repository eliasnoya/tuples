<?php

namespace Tuples\Http;

use Tuples\Http\Traits\ContainsMiddlewares;

class Router
{
    use ContainsMiddlewares;

    /**
     * Array of "method" => Route
     *
     * @var array
     */
    public array $routes;

    public function __construct(public string $baseUri = "")
    {
    }

    private function addRouteGroup(RouteGroup $routeGroup)
    {
        // inherit global-router middlewares
        $routeGroup->setMiddlewares($this->getMiddlewares());

        // load route for each method
        foreach ($routeGroup->getRoutes() as $route) {
            $this->addRoute($route);
        }
    }

    private function addRoute(Route $route)
    {
        // inherit global-router middlewares
        $route->setMiddlewares($this->getMiddlewares());

        // load route for each method
        $methods = $route->getMethods();
        foreach ($methods as $method) {
            $this->routes[$method][] = $route;
        }
    }

    public function add(Route|RouteGroup|array $route)
    {
        if ($route instanceof Route) {
            $this->addRoute($route);
        }

        if ($route instanceof RouteGroup) {
            $this->addRouteGroup($route);
        }

        if (is_array($route)) {
            /** @var Route $r */
            foreach ($route as $r) {
                $this->add($r);
            }
        }
    }

    /**
     * Search for a route based on the specified $method and $path.
     *
     * @param string $method The HTTP method (e.g., GET, POST).
     * @param string $path The path to match against existing routes.
     *
     * @return array    If a matchable route is found, returns an array with [Route, array of params => value].
     *                  If no matchable route is found, returns [false, []].
     */
    public function lookup(string $method, string $path): array
    {
        $path = str_replace($this->baseUri, "", $path);
        $routes = $this->routes[$method];

        /** @var Route $route */
        foreach ($routes as $route) {
            list($match, $params) = $route->matchsWith($path);

            if ($match) {
                return [$route, $params];
            }
        }

        return [false, []];
    }
}
