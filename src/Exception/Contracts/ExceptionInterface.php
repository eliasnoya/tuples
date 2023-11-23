<?php

namespace Tuples\Exception\Contracts;

use Tuples\Http\Response;

interface ExceptionInterface
{
    public function report(): void;
    public function response(): Response;
}
