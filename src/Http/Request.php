<?php

namespace Tuples\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tuples\Container\EphemeralContainer;
use Tuples\Container\Traits\HasContainer;
use Tuples\Utils\KeyValue;

/**
 * Usefull wrapper of an PSR7 ServerRequestInterface implementation
 */
class Request
{
    use HasContainer;

    // Query string parsed as KeyValue object
    private KeyValue $query;

    // Post body or json body parsed as KeyValue object
    private KeyValue $inputs;

    private array $files;

    // Server variables parsed as KeyValue object
    private KeyValue $server;

    // The current request route if exists
    private Route $route;

    // The current requested route params=>value
    private KeyValue $routeParams;

    /**
     * instance \Tuples\Http\Request with some PSR7 compliant library
     *
     * @param ServerRequestInterface $serverRequest
     */
    public function __construct(public ServerRequestInterface $serverRequest)
    {
        // Initialize easy-access QUERYSTRING KeyValue object
        $this->query = new KeyValue($serverRequest->getQueryParams());

        // Initialize easy-access SERVER KeyValue object
        $this->server = new KeyValue($serverRequest->getServerParams());

        // Initialize easy-access BODY KeyValue object
        $this->inputs = new KeyValue([]);
        $this->inputs->merge((array) $serverRequest->getParsedBody() ?? []);
        $this->inputs->mergeJson($serverRequest->getBody()->getContents());

        // \Psr\Http\Message\UploadedFileInterface[]
        $this->files = $serverRequest->getUploadedFiles();

        $this->bootContainer(new EphemeralContainer);
    }

    /**
     * Create from PSR Factory
     * This implementation is using Nyholm
     *
     * @return Request
     */
    public static function createFromGlobals(): Request
    {
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psr7Creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );
        return new Request($psr7Creator->fromGlobals());
    }

    /**
     * Get all the posted files with $index name
     *
     * @param string $index
     * @return UploadedFileInterface[]
     */
    public function files(string $index): array
    {
        if (!isset($this->files[$index])) {
            throw new InvalidArgumentException("No files in '$index' index");
        }

        return $this->files[$index];
    }

    /**
     * Get the first UploadedFileInterface posted with $index name
     *
     * @param string $index
     * @return UploadedFileInterface
     */
    public function file(string $index): UploadedFileInterface
    {
        $files = $this->files($index);

        return reset($files);
    }

    /**
     * Get current HTTP method
     *
     * @return string
     */
    public function method(): string
    {
        return strtoupper($this->serverRequest->getMethod());
    }

    public function ip(): string|null
    {
        return $this->server->get('REMOTE_ADDR');
    }

    /**
     * Get current request path
     *
     * @return string
     */
    public function path(): string
    {
        return $this->serverRequest->getUri()->getPath();
    }

    /**
     * Get index from Request body
     *
     * @param string $index
     * @param mixed $default
     * @return mixed
     */
    public function input(string $index, mixed $default = null): mixed
    {
        return $this->inputs->get($index, $default);
    }

    /**
     * Get index from Request query params
     *
     * @param string $index
     * @param mixed $default
     * @return mixed
     */
    public function query(string $index, mixed $default = null): mixed
    {
        return $this->query->get($index, $default);
    }

    public function inputs(): KeyValue
    {
        return $this->inputs;
    }

    public function headers(): array
    {
        return $this->serverRequest->getHeaders();
    }

    public function expectsJson(): bool
    {
        return $this->headerEqual("Accept", 'application/json');
    }

    public function expectHtml(): bool
    {
        return $this->headerEqual('Accept', 'text/html');
    }

    public function headerEqual(string $header, string $value): bool
    {
        foreach ($this->header($header) as $h) {
            foreach (explode(",", $h) as $hv) {
                if (strtolower($hv) === strtolower($value)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get index from header
     *
     * @param string $header
     * @return array
     */
    public function header(string $header): array
    {
        return $this->serverRequest->getHeader($header);
    }

    /**
     * Check if specific header exists on the request
     *
     * @param string $header
     * @return bool
     */
    public function headerExists(string $header): bool
    {
        return $this->serverRequest->hasHeader($header);
    }

    public function setRoute(Route $route)
    {
        $this->route = $route;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function setRouteParams(array $routeParams)
    {
        $this->routeParams = new KeyValue($routeParams);
    }

    public function getRouteParams(): KeyValue
    {
        return $this->routeParams;
    }
}
