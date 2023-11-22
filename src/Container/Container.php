<?php

namespace Tuples\Container;

use Tuples\Container\Contracts\AbstractContainer;

/**
 * Singleton Depedency Container
 */
class Container extends AbstractContainer
{
    /**
     * Hold the container instance
     *
     * @var Container
     */
    private static $instance = null;

    private function __construct()
    {
    }

    /**
     * Singleton implementation
     *
     * @return Container
     */
    public static function instance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
