<?php

namespace Tuples\Http;

use Tuples\Http\Traits\ContainsMiddlewares;

class Route
{
    use ContainsMiddlewares;

    private const HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

    /** Path of the route */
    private string $path;

    /** Path Regex */
    private string $pathPattern;

    /** Array of params without brackets */
    private array $routeParams = [];

    /** Array of params with brackets {} */
    private array $routeParamsRaw = [];

    private array $methods = ["GET"];

    private array $action;

    public function __construct(string $path, string|array $action, array $methods = ["GET"])
    {
        $this->parsePath($path);
        $this->setMethods($methods);
        $this->setAction($action);
    }

    /**
     * Add multiple methods to this route
     *
     * @param array $methods
     * @return Route
     */
    public function setMethods(array $methods)
    {
        foreach ($methods as $method) {
            $this->setMethod($method);
        }
        return $this;
    }

    /**
     * Add a method to this route
     *
     * @param string $method
     * @return Route
     */
    public function setMethod(string $method)
    {
        if (!in_array($method, self::HTTP_METHODS)) {
            throw new \Error("$method is not a valid HTTP Method");
        }

        // Avoid duplications
        if (!in_array($method, $this->methods)) {
            $this->methods[] = $method;
        }

        return $this;
    }

    /**
     * Set the path
     *
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->parsePath($path);
    }

    /**
     * Get the value of path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the value of methods
     *
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the value of action
     *
     * @return array
     */
    public function getAction(): array
    {
        return $this->action;
    }

    /**
     * Check if path match with this route
     *
     * Usage example:
     *
     * $route = Route::get("/hello/{name}/{id}", [RestController::class, 'hello']);
     * list($match, $params) = $route->matchWith("/hello/elias/10");
     *
     * output
     * $match => true
     * $params => ['name' => 'elias', 'id' => 10]
     *
     * @param string $path
     * @return array => [true if path match the route, array of params]
     */
    public function matchsWith(string $path): array
    {
        $bool = (bool) preg_match($this->pathPattern, $path, $matches);

        $params = [];
        // loop matched params excluding the full route at index 0
        unset($matches[0]);
        foreach ($matches as $index => $value) {
            // search the param fixing index
            $paramName = $this->routeParams[$index - 1];
            $params[$paramName] = $value;
        }

        return [$bool, $params];
    }

    /**
     * Specify the action for this route
     *
     * @param string|array $action
     * @return void
     */
    private function setAction(string|array $action)
    {
        // convert controller@action as array 0 class 1 method
        if (is_string($action)) {
            $action = explode("@", $action);
        }

        if (!isset($action[0], $action[1])) {
            throw new \Error("Invalid Action. Must have a CLASS and a METHOD");
        }

        $class = $action[0];
        $method = $action[1];

        if (!class_exists($class)) {
            throw new \Error("Invalid Action. Class $class doesnt exists");
        }

        if (!method_exists($class, $method)) {
            throw new \Error("Invalid Action. Method $method doesnt exists");
        }

        $this->action = [$class, $method];
    }

    /**
     * Parse path and params of the route
     *
     * @param string $path
     * @return void
     */
    private function parsePath(string $path): void
    {
        // remove last "/" if exists
        $this->path = strlen($path) > 1 ? rtrim($path, '/') : $path;

        // detect bracket params and load to the route, allways before parsePathPattern
        $params = [];
        preg_match_all('/\{([^\/]+)\}/', $this->path, $params);
        $this->routeParams = $params[1];
        $this->routeParamsRaw = $params[0];

        // replace bracket params with regex and load to the route
        // replace the / slashes with \/ for the expression
        $pattern = str_replace("/", "\/", $this->path);

        // replace route params with pattern
        foreach ($this->routeParamsRaw as $paramRaw) {
            $pattern = str_replace($paramRaw, "([a-zA-Z0-9-_.]+)", $pattern);
        }

        // \/? at end for match with optional ending slash
        $this->pathPattern = "~^$pattern\/?$~";
    }

    /*-----------------------------------
    | CONSTRUCTOR Shortcuts by methods
    |-----------------------------------*/

    public static function any(string $path, string|array $action)
    {
        return new Route($path, $action, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD']);
    }

    public static function get(string $path, string|array $action)
    {
        return new Route($path, $action);
    }

    public static function patch(string $path, string|array $action)
    {
        return new Route($path, $action, ["PATCH"]);
    }

    public static function post(string $path, string|array $action)
    {
        return new Route($path, $action, ["POST"]);
    }

    public static function put(string $path, string|array $action)
    {
        return new Route($path, $action, ["PUT"]);
    }

    public static function delete(string $path, string|array $action)
    {
        return new Route($path, $action, ["DELETE"]);
    }

    public static function options(string $path, string|array $action)
    {
        return new Route($path, $action, ["OPTIONS"]);
    }
}
