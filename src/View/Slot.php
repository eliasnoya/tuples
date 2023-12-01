<?php

namespace Tuples\View;

class Slot
{
    public function __construct(protected string $tag, protected string $raw, protected string $inner)
    {
    }

    public function getCloseTag(): string
    {
        return "</{$this->getTag()}>";
    }

    public function getOpenTag(): string
    {
        return "<{$this->getTag()}>";
    }

    public function getTag(): string
    {
        return "x-{$this->tag}";
    }

    /**
     * Get the value of raw
     *
     * @return string
     */
    public function getRaw(): string
    {
        return $this->raw;
    }

    /**
     * Get the value of inner
     *
     * @return string
     */
    public function getInner(): string
    {
        return $this->inner;
    }
}
