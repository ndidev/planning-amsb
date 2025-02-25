<?php

// Path: api/src/Core/HTTP/HTTPRequest.php

declare(strict_types=1);

namespace App\Core\HTTP;

use App\Core\Array\Server;
use App\Core\Exceptions\Server\ServerException;

/**
 * Represents an HTTP request.
 */
final readonly class HTTPRequest
{

    /**
     * HTTP request method.
     */
    private string $method;

    /**
     * HTTP request headers.
     * @var array<string, string>
     */
    protected array $headers;

    /**
     * Request ETag.
     */
    public ?string $etag;

    /**
     * Request URL path.
     */
    public string $path;

    /**
     * Request query parameters (after "?").
     */
    private HTTPRequestQuery $query;

    /**
     * Request body.
     */
    private HTTPRequestBody $body;

    /**
     * `true` if the request is a CORS preflight request.
     */
    public bool $isPreflight;

    public function __construct()
    {
        $this->method = \strtoupper(Server::getString('REQUEST_METHOD', 'GET'));

        if (\function_exists('\getallheaders')) {
            /** @var array<string, string> */
            $allHeaders = \getallheaders();
            $this->headers = \array_change_key_case($allHeaders, CASE_LOWER);
        } else {
            $this->headers = [];
        }

        $path = \parse_url(Server::getString('REQUEST_URI'), PHP_URL_PATH);
        if (!\is_string($path)) {
            throw new ServerException("The request path is not a string.");
        }
        $this->path = $path;

        $this->query = new HTTPRequestQuery();

        $this->body = new HTTPRequestBody();

        $this->etag = $this->headers["If-None-Match"] ?? null;

        if (
            $this->method === "OPTIONS"
            && \array_key_exists("access-control-request-method", $this->headers)
            && \array_key_exists("origin", $this->headers)
        ) {
            $this->isPreflight = true;
        } else {
            $this->isPreflight = false;
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the request query parameters.
     * 
     * @return HTTPRequestQuery Request query object.
     */
    public function getQuery(): HTTPRequestQuery
    {
        return $this->query;
    }

    /**
     * Get a request body.
     * 
     * @return HTTPRequestBody Request body object.
     */
    public function getBody(): HTTPRequestBody
    {
        return $this->body;
    }
}
