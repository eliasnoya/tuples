<?php

namespace Tuples\Http\Contracts;

use Tuples\Database\Database;
use Tuples\Http\Request;
use Tuples\Http\Response;
use Tuples\Database\DatabasePool;

abstract class Controller
{
    public function __construct(protected Request $req, protected Response $res, protected DatabasePool $dbPool)
    {
    }

    public function db($connection = 'default'): Database
    {
        return $this->dbPool->get($connection);
    }
}
