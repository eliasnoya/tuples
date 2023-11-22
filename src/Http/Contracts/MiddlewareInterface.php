<?php

namespace Tuples\Http\Contracts;

use Tuples\Http\Request;
use Tuples\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, Response $response, \Closure $next);
}
