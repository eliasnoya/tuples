<?php

namespace Tuples\Http\Contracts;

use Tuples\Http\Request;
use Tuples\Http\Response;

abstract class Controller
{
    public function __construct(protected Request $req, protected Response $res)
    {
    }
}
