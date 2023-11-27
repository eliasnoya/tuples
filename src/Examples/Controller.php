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
        throw new \Error("test");
        return view("test.html", ['content' => 'Hello world']);
    }
}
