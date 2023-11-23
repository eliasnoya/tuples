<?php

namespace Tuples\Http\Contracts;

interface MiddlewareInterface
{
    public function handle(\Closure $next);
}
