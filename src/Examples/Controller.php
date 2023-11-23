<?php

namespace Tuples\Examples;

use Tuples\Http\Contracts\Controller as BaseController;

class Controller extends BaseController
{
    public function index()
    {
        return routeTo('/page', "GET", ["Hello" => "Elias"]);
    }

    public function page()
    {
        return [
            "I'am the page",
            $this->req->query("hello"),
            $this->req->input("Hello"),
        ];
    }
}
