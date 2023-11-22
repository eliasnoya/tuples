<?php

require_once 'vendor/autoload.php';

/**
 *
 * Initializes the App, this one is designed for roadrunner Worker.
 * It creates an instance of Container along with Tuples\Request and Tuples\Response during startup.
 */
$app = app(); // no basepath -> store at project root

$app->use(\Tuples\Http\Middleware\Cors::class);

// Utilize Tuples\DatabasePool and bind the default connection to the container
$app->useDefaultDatabase(env('DEFAULT_DB_DSN'), env('DEFAULT_DB_USER'), env('DEFAULT_DB_PASS'));

// Add another database by using the first parameter $connName to differentiate it from the "default" connection
// $app->useDatabase("secondary", "...", "...", "...", []);

// Add routes from a file that returns an array with Route and RouteGroup instances
$app->routes(include_once 'routes/core.php');

// Add a single Route or RouteGroup instance to the router if needed
// $app->route(Route::get("/", [Controller::class, 'index']));

// Wait for requests
$app->work();
