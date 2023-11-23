<?php

namespace Tuples\Container;

class Dependency
{
    private const DEPENDENCY_TYPES = ["singleton", "callable"];

    private mixed $concrete;

    public function __construct(private string $name, mixed $concrete, private string $type)
    {
        if (!in_array($type, self::DEPENDENCY_TYPES)) {
            throw new \InvalidArgumentException("type $type is not a valid depedency type. Valids: " . implode(", ", self::DEPENDENCY_TYPES));
        }

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
        return $this->type === 'singleton';
    }

    public function getType(): string
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
