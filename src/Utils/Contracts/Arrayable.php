<?php

namespace Tuples\Utils\Contracts;

/**
 * Convert a Class with an array $data property on an "Arrayable" class
 * (arrayaccess, iterator, jsonserializable and serializable)
 */
abstract class Arrayable implements \ArrayAccess, \Iterator, \JsonSerializable, \Serializable
{
    protected array $data;

    // Implement the serialize method from Serializable
    public function serialize()
    {
        return serialize($this->data);
    }

    // Implement the unserialize method from Serializable
    public function unserialize($serialized)
    {
        $this->data = unserialize($serialized);
    }

    // Implement the jsonSerialize method from JsonSerializable
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    // ArrayAccess methods
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    // Implement the iterator methods
    public function current(): mixed
    {
        return current($this->data);
    }

    public function key(): string|int|null
    {
        return key($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function rewind(): void
    {
        reset($this->data);
    }

    public function valid(): bool
    {
        return key($this->data) !== null;
    }
}
