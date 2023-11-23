<?php

namespace Tuples\Exception\Contracts;

use Tuples\Http\Request;
use Tuples\Http\Response;

abstract class ExceptionHandler implements ExceptionInterface
{
    public function __construct(protected \Throwable|ExceptionInterface $error, protected Request $request, protected Response $response)
    {
    }
}
