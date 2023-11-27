<?php

namespace Tuples\View\Contracts;

interface ViewManagerInterface
{
    public function render(string $template, array $data = []): string;
}
