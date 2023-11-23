<?php

namespace Tuples\Http;

use Tuples\Http\Traits\ContainsMiddlewares;

class RouteGroup
{
    use ContainsMiddlewares;

    /** @var Route[] */
    private array $routes;

    public function __construct(private string $prefix)
    {
    }

    public static function prefix(string $prefix): RouteGroup
    {
        return new RouteGroup($prefix);
    }

    public function add(array $routes): RouteGroup
    {
        if (!is_array($routes) || empty($routes)) {
            throw new \Error("add() callback must return an array of \Tuples\Http\Route instances");
        }

        foreach ($routes as $route) {
            $this->addRoute($route);
        }
        return $this;
    }

    public function addRoute(Route $route): RouteGroup
    {
        $currentPath = $route->getPath();
        // if path doesnt start with slash / add it as glue between prefix and route path

        $glue = substr($currentPath, 0, 1) != '/' ? "/" : "";
        $newPath = $this->getPrefix() . $glue . $currentPath;

        // append prefix to the Path
        $route->setPath($newPath);

        // inherit middlewares
        foreach ($this->middlewares as $middleware) {
            $route->setMiddleware($middleware);
        }

        $this->routes[] = $route;
        return $this;
    }

    /**
     * Get the value of prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get the value of routes
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
