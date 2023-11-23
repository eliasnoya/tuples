<?php

namespace Tuples\Http\Middleware;

use Tuples\Http\Contracts\Middleware;

class Gzip extends Middleware
{

    public function handle(\Closure $next)
    {
        $this->res->gzip();
        return $next();
    }
}
