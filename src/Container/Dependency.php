<?php

namespace Tuples\Container;

class Dependency
{
    private mixed $concrete;

    public function __construct(private string $name, mixed $concrete, private DependencyType $type)
    {
        if (!$concrete instanceof \Closure && !is_object($concrete) && !is_string($concrete)) {
            throw new \InvalidArgumentException("concrete must be a closure or object");
        }

        $this->concrete = $concrete;
    }

    public function isClassName(): bool
    {
        return is_string($this->concrete) && class_exists($this->concrete);
    }

    public function isObject(): bool
    {
        return is_object($this->concrete) && !$this->concrete instanceof \Closure;
    }

    public function isClosure(): bool
    {
        return $this->concrete instanceof \Closure;
    }

    public function isSingleton(): bool
    {
        return $this->type === DependencyType::SINGLETON;
    }

    public function getType(): DependencyType
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConcrete(): mixed
    {
        return $this->concrete;
    }
}
