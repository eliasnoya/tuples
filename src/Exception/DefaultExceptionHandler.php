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

        // Non implemented ExceptionInterface, autogenerate response
        $this->response->status($this->getHttpCode());

        // If request is json return json body
        if ($this->request->expectJson()) {
            $body = [
                'error' => true,
                'message' => $this->error->getMessage(),
            ];
            if (env("ENVIORMENT") == 'dev') {
                $body["trace"] = $this->error->getTrace();
            }

            $this->response->isJson()->body($body);
        } else {
            // Not Json, return text
            $this->response->body("Error " . $this->getHttpCode() . ": " . $this->error->getMessage());
        }

        return $this->response;
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
