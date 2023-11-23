<?php

namespace Tuples\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Usefull wrapper to PSR7 ReponseInterface implementation
 */
class Response
{
    private bool $gzip = false;

    /**
     * Instance with a ResponseInterface PSR7 implementation of your choice
     *
     * @param ResponseInterface $standard
     */
    public function __construct(private ResponseInterface $psr)
    {
    }

    public function unsetHeader(string $header): self
    {
        $this->psr = $this->psr->withoutHeader($header);
        return $this;
    }

    public function header(string $header, string $value): self
    {
        $this->psr = $this->psr->withHeader($header, $value);
        return $this;
    }

    public function status(int $status): self
    {
        $this->psr = $this->psr->withStatus($status);

        return $this;
    }

    public function gzip()
    {
        $this->psr = $this->psr->withHeader('Content-Encoding', 'gzip');
        $this->gzip = true;

        return $this;
    }

    public function body(mixed $content): self
    {
        if (is_array($content) || is_object($content)) {
            $body = json_encode($content);
        } else {
            $body = (string) $content;
        }

        if ($this->gzip) {
            $body = gzencode($body);
        }

        /** @var StreamInterface $stream */
        $stream = \Nyholm\Psr7\Stream::create($body);

        $this->psr = $this->psr->withBody($stream);
        $this->psr = $this->psr->withHeader('Content-Length', $stream->getSize());

        return $this;
    }

    public function matchRequestContent(Request $request, bool $overwrite = false)
    {
        $exists = $this->psr()->hasHeader('content-type');
        if ($overwrite) {
            $exists = false;
        }

        if ($request->headerExists('accept') && !$exists) {
            $accept = $request->header('accept');
            $accepts = explode(",", $accept[0]);

            if (isset($accepts[0])) {
                $this->header("Content-type", $accepts[0]);
            }
        }
    }

    public function isJson(): self
    {
        return $this->header('Content-Type', 'application/json');
    }

    /**
     * Get the PSR7 Response Instance
     *
     * @return ResponseInterface
     */
    public function psr(): ResponseInterface
    {
        return $this->psr;
    }

    /**
     * Get body of response
     *
     * @return string
     */
    public function content(): string
    {
        return $this->psr->getBody()->getContents();
    }

    public function redirect(string $to): self
    {
        return $this->header("Location", $to)->status("302")->body("");
    }

    /**
     * Write response (only usefull on FPM/CGI implementations)
     *
     * @return void
     */
    public function emit()
    {
        // Send status line
        header(sprintf(
            'HTTP/%s %s %s',
            $this->psr->getProtocolVersion(),
            $this->psr->getStatusCode(),
            $this->psr->getReasonPhrase()
        ), true, $this->psr->getStatusCode());

        // Send headers
        foreach ($this->psr->getHeaders() as $header => $value) {
            header($header . ":" . implode(',', $value), false);
        }

        // Send the response body
        echo $this->psr->getBody();
        exit;
    }
}
