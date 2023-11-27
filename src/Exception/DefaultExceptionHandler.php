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

        // If request is json return json body
        if ($this->req->expectsJson()) {
            $body = [
                'error' => true,
                'message' => $this->error->getMessage(),
            ];
            if (isDev()) {
                $body["trace"] = $this->error->getTrace();
            }
            $this->res->json($body);
        } elseif ($this->req->expectHtml()) {
            $template = view("errors/exception.html.twig", [
                'code' => $this->getHttpCode(),
                'message' => $this->error->getMessage(),
                'trace' => $this->error->getTrace()
            ]);
            $this->res->body($template);
        } else {
            // Not Json, return text
            $body = "Error " . $this->getHttpCode() . ": " . $this->error->getMessage();
            $this->res->body($body);
        }
        $this->res->status($this->getHttpCode());

        return $this->res;
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
