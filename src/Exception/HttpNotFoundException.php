<?php

namespace Tuples\Exception;

use Exception;

class HttpNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct("Not found", 404);
    }
}
