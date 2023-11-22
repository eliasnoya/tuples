<?php

namespace Tuples\Http\Contracts;

use Tuples\Database\Database;

abstract class Controller
{

    public function db(string $conn = 'default'): Database
    {
        return db($conn);
    }
}
