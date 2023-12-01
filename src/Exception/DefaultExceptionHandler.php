<?php

namespace Tuples\Exception;

use Tuples\Exception\Contracts\ExceptionHandler;
use Tuples\Http\Response;

class DefaultExceptionHandler extends ExceptionHandler
{

    public function report(): void
    {
        error_log($this->error->getMessage());
    }

    public function response(): Response
    {
        // If is an implementation of ExceptionInterface execute his own method
        if (method_exists($this->error, 'response')) {
            return $this->error->response();
        }

        // Prepare body according expectation
        $body = match (true) {
            $this->req->expectsJson() => $this->json(),
            $this->req->expectHtml() => $this->html(),
            default => $this->plain()
        };

        return $this->res->body($body)->status($this->getHttpCode());
    }

    private function json(): array
    {
        $body = [
            'error' => true,
            'message' => $this->error->getMessage(),
        ];
        if (isDev()) {
            $body["trace"] = $this->error->getTrace();
        }

        return $body;
    }

    private function html(): string
    {
        return view("errors.exception", [
            'code' => $this->getHttpCode(),
            'message' => $this->error->getMessage(),
            'trace' => isDev() ? $this->error->getTrace() : false
        ])->render();
    }

    private function plain(): string
    {
        return "Error " . $this->getHttpCode() . ": " . $this->error->getMessage();
    }

    public function getHttpCode(): int
    {
        $code = $this->error->getCode();
        if ($code >= 100 && $code <= 599) {
            return $code;
        }
        return 500;
    }
}
