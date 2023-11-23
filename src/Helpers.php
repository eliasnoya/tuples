<?php

use Tuples\Container\Container;
use Tuples\Database\Database;
use Tuples\Database\DatabasePool;
use Tuples\Http\Request;
use Tuples\Http\Response;
use Tuples\Http\Router;
use Tuples\Integration\App;
use Tuples\Integration\Resolver;

/**
 * Get container instance
 *
 * @param mixed $abstract
 * @param array $arguments
 * @return Container
 */
function container(): Container
{
    return Container::instance();
}

/**
 * Get database pool singleton dependency
 *
 * @return DatabasePool
 */
function dbPool(): DatabasePool
{
    return container()->resolve(DatabasePool::class);
}

/**
 * Get DB from de database pool
 *
 * @param string $conn
 * @return Database
 */
function db($conn = 'default'): Database
{
    return dbPool()->get($conn);
}

/**
 * Get the Router (for Http App)
 *
 * @return Router
 */
function router(): Router
{
    return container()->resolve(Router::class);
}

/**
 * Get current request instance
 *
 * @return Request
 */
function request(): Request
{
    return container()->resolve(Request::class);
}

/**
 * Get current response instance
 *
 * @return Response
 */
function response(): Response
{
    return container()->resolve(Response::class);
}

/**
 * Get enviorment variable or default
 *
 * @param string $index
 * @param mixed $default
 * @return mixed
 */
function env(string $index, mixed $default = null): mixed
{
    return isset($_ENV[$index]) ? $_ENV[$index] : $default;
}

/**
 * Base path of the project
 * @see Tuples\Integration\PhpBootstrapper to check initialization
 *
 * @param string $path
 * @return string
 */
function basePath(string $path = ''): string
{
    return env('base_path') . $path;
}

/**
 * Storage path of the project
 * @see Tuples\Integration\PhpBootstrapper to check initialization
 *
 * @param string $path
 * @return string
 */
function storagePath(string $path = ''): string
{
    return basePath('/storage' . $path);
}

/**
 * Get Default APP-BOOTSTRAP
 *
 * @return App
 */
function app(string $basePath = "./"): App
{
    $app = new App($basePath);

    // Basic Settings with DotEnv, Router and minium php config with PhpBootstrapper
    // You can Extends \Tuples\Integration\App and customize your own bootstrap.
    // You must register \Tuples\Http\Router and ensure the existence of base_path and storage_path in $_ENV
    $app->defaults();

    return $app;
}

/**
 * Perform an internal redirection using the Route resolver without changing the URL or making an HTTP call.
 * For HTTP location redirects, use response()->redirect(...).
 * If the destination route contains RouteParams in the $path, provide the values directly (e.g., /user/1, not /user/{id}).
 * You can pass manual input data to the destination route using the $inputs variable
 * (remember the inputs sended to the first route will reach the next route to)
 *
 * @param string $path
 * @param string $method
 * @return Response
 */
function routeTo(string $path, string $method = "GET", array $inputs = []): Response
{
    request()->inputs()->merge($inputs);

    /** @var Resolver $resolver */
    $resolver = container()->resolve(Resolver::class);
    return $resolver->resolvePath($method, $path);
}

function castAsMultidimensional(array $inputArray): array
{
    // If $inputArray is not an array, wrap it in another array
    if (!is_array($inputArray)) {
        $inputArray = [$inputArray];
    }

    // If $inputArray is a single-dimensional array, wrap it in another array
    if (!is_array($inputArray[0])) {
        $inputArray = [$inputArray];
    }

    return $inputArray;
}
