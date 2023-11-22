<?php

namespace Tuples\Utils;

use Tuples\Utils\Contracts\Arrayable;

class KeyValue extends Arrayable
{

    public function __construct(protected array $data)
    {
    }

    public function __set(string $index, mixed $value): void
    {
        $this->set($index, $value);
    }

    public function __get($index)
    {
        return $this->get($index);
    }

    public function set(string $index, mixed $value): void
    {
        $this->data[$index] = $value;
    }

    public function get(string $index, mixed $default = null): mixed
    {
        return isset($this->data[$index]) ? $this->data[$index] : $default;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function exists(string $index): bool
    {
        $value = $this->get($index, false);
        return ($value && !empty($value)) ? true : false;
    }

    public function isEqual(string $index, mixed $needle): bool
    {
        $value = $this->get($index);
        return $value === $needle;
    }

    public function contains(string $index, string $needle): bool
    {
        $value = $this->get($index);

        if (is_array($value)) {
            foreach ($value as $v) {
                if (str_contains($v, $needle)) {
                    return true;
                }
            }
            return false;
        }

        return str_contains($value, $needle);
    }

    public function merge(array $values): void
    {
        foreach ($values as $index => $value) {
            $this->set($index, $value);
        }
    }

    public function mergeJson(string $values): void
    {
        $values = json_decode($values, true);
        if (!empty($values) && is_array($values)) {
            $this->merge($values);
        }
    }
}
