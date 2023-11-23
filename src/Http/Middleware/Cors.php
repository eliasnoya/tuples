<?php

namespace Tuples\Http\Middleware;

use Tuples\Http\Contracts\Middleware;

class Cors extends Middleware
{

    public function handle(\Closure $next)
    {
        $this->res->header('Access-Control-Allow-Origin', env('CORS_ALLOW_ORIGIN', '*'))
            ->header('Access-Control-Allow-Methods', env('CORS_ALLOW_METHODS', 'GET, POST, PUT, PATCH, DELETE, OPTIONS'))
            ->header('Access-Control-Allow-Headers', env('CORS_ALLOW_HEADERS', 'Content-Type, Authorization, Accept, Expect'));

        // Handle preflight requests
        if ($this->req->method() === 'OPTIONS') {
            return $this->res->status(200);
        }

        return $next();
    }
}
