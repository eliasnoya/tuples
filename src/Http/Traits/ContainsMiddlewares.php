<?php

namespace Tuples\Http\Traits;

trait ContainsMiddlewares
{
    protected array $middlewares = [];

    /**
     * Add multiples middlewares to $middlewares container
     *
     * @param array $middlewares
     * @return self
     */
    public function setMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            $this->setMiddleware($middleware);
        }
        return $this;
    }

    /**
     * Add a middleware to $middlewares container
     *
     * @param string $middleware
     * @return self
     * @throws \Error if middleware class doesnt exists
     */
    public function setMiddleware(string $middleware)
    {
        if (!class_exists($middleware)) {
            throw new \Error("Middleware $middleware doesnt exists");
        }

        // Avoid duplications
        if (!in_array($middleware, $this->middlewares)) {
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Get middlewares value
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
