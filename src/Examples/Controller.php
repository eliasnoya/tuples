<?php

namespace Tuples\Examples;

use Tuples\Http\Contracts\Controller as BaseController;
use Tuples\Http\Request;

class User
{
    public int $id = 1;

    public function __construct(public Tenant $tenant)
    {
    }
}

class Tenant
{
    public int $id = 1;
}

class Controller extends BaseController
{
    public function index(Request $request)
    {
        $request->container()->singleton(Tenant::class, Tenant::class);
        $request->container()->singleton(User::class, User::class);

        return [
            'must exists both',
            $request->container()->resolve(Tenant::class),
            $request->container()->resolve(User::class),
        ];
    }

    public function index2(Request $request)
    {
        return [
            'must not exists',
            $request->container()->resolve(Tenant::class),
            $request->container()->resolve(User::class),
        ];
    }
}
