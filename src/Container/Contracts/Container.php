<?php

namespace Tuples\Container\Contracts;

use Tuples\Container\{Dependency, DependencyType};

/**
 * Abstract class providing methods for adding, resolving, and injecting dependencies in classes that extend it.
 * Two specific implementations are showcased:
 *   - @see \Tuples\Container\Container: Application-centric container (singleton pattern)
 *   - @see \Tuples\Container\EphemeralContainer: Context-centric container (No pattern at all, only a class)
 */
abstract class Container
{
    /**
     * Dependencies array assoc
     *
     * @var array
     */
    private array $dependencies;

    public function exists(string $name): bool
    {
        return isset($this->dependencies[$name]);
    }

    /**
     * Bind abstract & concrete to $dependencies array
     *
     * @param string $name
     * @param mixed $concrete
     * @param DependencyType $type
     * @return void
     */
    private function bind(string $name, mixed $concrete, DependencyType $type): void
    {
        $this->dependencies[$name] = new Dependency($name, $concrete, $type);
    }

    /**
     * Remove from container, usefull for worker-not-dying-context
     *
     * @param string $name
     * @return void
     */
    public function unbind(string $name): void
    {
        unset($this->dependencies[$name]);
    }

    /**
     * This dependency is a binding object, no resolution needed
     * UseCase in the framework: Request, Response, Router, DatabasePool
     *
     * NOTE: in the cases of Request/Response exists two implementations
     *  - worker-not-dying-context => singleton Request, and at the end of the request-lifecycle unbind
     *  - standard-dying-context => singleton Request, and thats it
     *  - @see \Tuples\Integration\App (methods: work() and emit())
     *
     * @param string $name
     * @param mixed $concrete
     * @return void
     */
    public function singleton(string $name, mixed $concrete): void
    {
        $this->bind($name, $concrete, DependencyType::SINGLETON);
    }

    /**
     * This dependency will be resolved/instanced every time you request it
     * UseCase in the framework: BIND and RESOLVE controllers and middlewares
     *
     * @param string $name
     * @param \Closure $concrete
     * @return void
     */
    public function callable(string $name, mixed $concrete): void
    {
        $this->bind($name, $concrete, DependencyType::INSTANCEABLE);
    }

    /**
     * Resolve the dependency to get an instance of the desire object
     *
     * @param string $name
     * @param array $arguments
     * @return object
     */
    public function resolve(string $name, array $arguments = []): object
    {
        if (!isset($this->dependencies[$name])) {
            throw new \InvalidArgumentException("Depedency $name is not defined.");
        }

        /** @var Dependency $dependency */
        $dependency = $this->dependencies[$name];

        $instance = null;

        // Resolve unresolved or callable dependency
        if ($dependency->isClosure() || $dependency->isClassName()) {
            $instance = $this->instanceDependency($dependency, $arguments);
        }

        // first time of a singleton closure or classname will be re-binded as intance instead a callback
        if (($dependency->isClosure() || $dependency->isClassName()) && $dependency->isSingleton()) {
            $this->bind($dependency->getName(), $instance, $dependency->getType());
        }

        // already resolved dependency instance
        if ($dependency->isObject()) {
            $instance = $dependency->getConcrete();
        }

        // no resolution?
        if (empty($instance)) {
            throw new \InvalidArgumentException("Depedency $dependency can not be resolved.");
        }

        return $instance;
    }

    /**
     * Executes a method from a dependency
     *
     * @param string $name
     * @param string $method
     * @return mixed
     */
    public function resolveAndExecute(string $name, string $method, array $args = []): mixed
    {
        $depedency = $this->dependencies[$name];

        $instance = $this->resolve($name);

        // At this moment after resolve() the dependency will be allways a object.
        $this->injectArguments($depedency, $args, $method);

        return call_user_func_array([$instance, $method], $args);
    }

    /**
     * Instance a dependency from a ClassName or a Closure Injecting other dependencies if exists
     *
     * @param Dependency $dependency
     * @param array $args
     * @return object
     */
    private function instanceDependency(Dependency $dependency, array $args): object
    {
        if ($dependency->isClassName()) {
            $this->injectArguments($dependency, $args, '__construct');
            $class = $dependency->getConcrete();
            return new $class(...$args);
        }

        if ($dependency->isClosure()) {
            $this->injectArguments($dependency, $args);
            return call_user_func_array($dependency->getConcrete(), $args);
        }
    }

    /**
     * Inject depedencies to $args variable
     *
     * @param string $class
     * @param array $args
     * @param string $method For class depedencies only
     * @return void
     */
    private function injectArguments(Dependency $dependency, array &$args, string|null $method = null): void
    {
        try {
            $params = [];

            if ($dependency->isClassName()) {
                // Create a ReflectionClass object for the class
                $reflection = new \ReflectionClass($dependency->getConcrete());
                // Get the method
                $method = $reflection->getMethod($method);
                // If method exists check if some dependency must be injected
                // Get the parameters of the constructor
                $params = $method->getParameters();
            }

            if ($dependency->isClosure()) {
                // Create a ReflectionFunction object for the closure
                $reflection = new \ReflectionFunction($dependency->getConcrete());
                // Get the parameters of the closure
                $params = $reflection->getParameters();
            }

            foreach ($params as $param) {

                $paramName = $param->getName();

                /** @var \ReflectionNamedType|\ReflectionUnionType */
                $type = $param->getType();

                if ($type instanceof \ReflectionUnionType) {
                    $typeToInject = $type;
                } else {
                    $typeToInject = $type ? $type->getName() : null;
                }

                // if the Type is load to container....inject the parameter
                if ($typeToInject && $this->exists($typeToInject)) {
                    $args[$paramName] = $this->resolve($typeToInject);
                }
            }
        } catch (\Throwable $th) {
            // use system error log
            error_log("Container::injectArguments() not-critical warning: " . $th->getMessage());
        }
    }
}
