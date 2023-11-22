<?php

namespace Tuples\Http\Middleware;

use Tuples\Http\Contracts\Middleware;
use Tuples\Http\Request;
use Tuples\Http\Response;

class Cors extends Middleware
{

    public function handle(Request $request, Response $response, \Closure $next)
    {
        $response->header('Access-Control-Allow-Origin', env('CORS_ALLOW_ORIGIN', '*'))
            ->header('Access-Control-Allow-Methods', env('CORS_ALLOW_METHODS', 'GET, POST, PUT, PATCH, DELETE, OPTIONS'))
            ->header('Access-Control-Allow-Headers', env('CORS_ALLOW_HEADERS', 'Content-Type, Authorization, Accept, Expect'));

        // Handle preflight requests
        if ($request->method() === 'OPTIONS') {
            return $response->status(200);
        }

        return $next();
    }
}
