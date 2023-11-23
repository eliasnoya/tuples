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

// Example on how and when use Request EphemeralContainer on a Worker context (Roadrunner)
// Is not usefull on FPM or similar php enviorment when the hole application born and die with the request

class Controller extends BaseController
{
    public function index(Request $request)
    {
        // if this is set on a middleware will live in entire request
        $request->singleton(Tenant::class, Tenant::class);
        $request->singleton(User::class, User::class);

        return [
            'must exists both',
            $request->resolve(Tenant::class),
            $request->resolve(User::class),
        ];
    }

    public function index2(Request $request)
    {
        // hint first index and then index2, the dependency doesnt exists anymore
        return [
            'must not exists',
            $request->resolve(Tenant::class),
            $request->resolve(User::class),
        ];
    }
}
