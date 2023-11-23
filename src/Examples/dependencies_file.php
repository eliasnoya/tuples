<?php

class User
{
    public $id = 1;
    public function __construct(public Tenant $tenant)
    {
    }
}

class Tenant
{
    public $id = 1;
}

/**
 * Each item must be:
 * DepedencyName (unique name), DendencyConcrete (callback, object or classname), DependencyType ('singleton' or 'callable')
 */
return [
    [
        Tenant::class,
        Tenant::class,
        'singleton'
    ],
    [
        User::class,
        User::class,
        'singleton'
    ],
];
