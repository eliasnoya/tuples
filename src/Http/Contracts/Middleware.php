<?php

namespace Tuples\Http\Contracts;

use Tuples\Http\Request;
use Tuples\Http\Response;

abstract class Middleware implements MiddlewareInterface
{
    public Request $req;
    public Response $res;

    public function setContext(Request $request, Response $response)
    {
        $this->req = $request;
        $this->res = $response;
    }
}
