<?php

namespace Tuples\View;

use Tuples\View\Contracts\ViewManagerInterface;

/**
 * Wrapper for Twig
 *
 * Do not use this class directly. Instead, use it through the Container to avoid re-initializing Twig dependencies.
 * This wrapper only executes the render method and merges the $request dependency.
 *
 */
class Twig implements ViewManagerInterface
{
    public function __construct(protected \Twig\Environment $twig)
    {
    }

    /**
     * Process the template and return the content as string
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        // append request instance
        $data = array_merge($data, [
            'request' => request(),
        ]);

        return $this->twig->render($template, $data);
    }

    public function getTwigEnviorment(): \Twig\Environment
    {
        return $this->twig;
    }
}
