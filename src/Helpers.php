<?php

use Tuples\Container\Container;
use Tuples\Database\Database;
use Tuples\Database\DatabasePool;
use Tuples\Http\Request;
use Tuples\Http\Response;
use Tuples\Http\Router;
use Tuples\Integration\RouteResolver;
use Tuples\View\Template;

/**
 * Get global-container instance
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

function isDev(): bool
{
    return env("ENVIORMENT") === 'dev';
}

function isProduction(): bool
{
    return env("ENVIORMENT") === 'production';
}

function isStage(): bool
{
    return env("ENVIORMENT") === 'stage';
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
 * Get the root path of view
 *
 * @return string
 */
function viewsPath(string $path = ''): string
{
    return env('views_path') . $path;
}

/**
 * Shortcut to render view
 *
 * @param string $template
 * @param array $data
 * @return Template
 */
function view(string $template, array $data = []): Template
{
    // support dot notation for folders
    $template = str_replace('.', '/', ltrim($template, '/'));

    // Add .php extension if not set
    if (substr($template, -4) != '.php') {
        $template .= '.php';
    }

    // Add basic dependencies to the template data
    $data = array_merge($data, [
        'req' => request(),
        'db' => db(),
        'public' => basePath('/public'),
    ]);

    return new Template(viewsPath("/$template"), $data);
}

/**
 * Perform an internal redirection using the Route resolver without changing the URL / making an HTTP call.
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

    /** @var RouteResolver $resolver */
    $resolver = container()->resolve(RouteResolver::class);
    return $resolver->executeFromPath($method, $path);
}
