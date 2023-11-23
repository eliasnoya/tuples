<?php

namespace Tuples\Container\Traits;

use Tuples\Container\Container as SingletonContainer;
use Tuples\Container\Contracts\Container;

/**
 * A trait designed to indicate that the class has a container property and provides convenient methods for handling it efficiently.
 */
trait HasContainer
{
    /**
     * The container instance for dependency management.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Sets the container of this class.
     * This method must be called at object initialization
     *
     * @return void
     */
    private function bootContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Sets a custom container (must extend Container).
     * This method must be called in the class constructor.
     *
     * @param Container $container The custom container instance.
     * @return void
     */
    private function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Obtains the instance of the container.
     *
     * @return Container The container instance.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Shortcut to register a singleton dependency.
     *
     * @param string $name The name of the dependency.
     * @param mixed $concrete The concrete implementation.
     * @return void
     */
    public function singleton(string $name, mixed $concrete): void
    {
        $this->container->singleton($name, $concrete);
    }

    /**
     * Shortcut to register a callable dependency.
     *
     * @param string $name The name of the dependency.
     * @param mixed $concrete The concrete implementation.
     * @return void
     */
    public function callable(string $name, mixed $concrete): void
    {
        $this->container->callable($name, $concrete);
    }

    /**
     * Shortcut to resolve a dependency.
     *
     * @param string $name The name of the dependency.
     * @param array $arguments Optional arguments for dependency resolution.
     * @return object The resolved instance.
     */
    public function resolve(string $name, array $arguments = []): object
    {
        return $this->container->resolve($name, $arguments);
    }

    /**
     * Shortcut to Execute a method (with injectons) from a registered Dependency
     *
     * @param string $name
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function resolveAndExecute(string $name, string $method, array $args = []): mixed
    {
        return $this->container->resolveAndExecute($name, $method, $args);
    }

    /**
     * Shortcut to release a Depedency
     *
     * @param string $name
     * @return void
     */
    public function unbind(string $name): void
    {
        $this->container->unbind($name);
    }

    /**
     * Check if depedency exists in container
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->container->exists($name);
    }

    /**
     * Shorcut to bind array of dependencies
     *
     * @param array $dependencies
     * @return void
     */
    public function bindDependencies(array $dependencies): void
    {
        $this->container->bindDependencies($dependencies);
    }
}
