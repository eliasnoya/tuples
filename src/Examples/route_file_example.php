<?php

use Tuples\Examples\RestController;
use Tuples\Http\Route;
use Tuples\Http\RouteGroup;

return [

    Route::get("/", [RestController::class, 'index']),

    RouteGroup::prefix("/user")->add(function () {
        return [
            Route::get("/", [RestController::class, 'index']),
            Route::get("/{name}", [RestController::class, 'hello'])
        ];
    }),

];
