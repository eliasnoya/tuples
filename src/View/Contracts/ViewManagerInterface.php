<?php

namespace Tuples\View\Contracts;

interface ViewManagerInterface
{
    public function prepare(string $template, array $data = []): ViewManagerInterface;
    public function render(): string;
}
