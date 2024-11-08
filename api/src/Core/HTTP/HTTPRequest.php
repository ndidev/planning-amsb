<?php

namespace App\Core\HTTP;

/**
 * Represents an HTTP request.
 * 
 * @phpstan-import-type QueryArray from HTTPRequestQuery
 */
final class HTTPRequest
{

    /**
     * HTTP request method.
     */
    private string $method;

    /**
     * HTTP request headers.
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * Request ETag.
     */
    public readonly ?string $etag;

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
    public readonly bool $isPreflight;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (function_exists('getallheaders')) {
            $this->headers = getallheaders();
        }

        $this->query = HTTPRequestQuery::buildFromRequest();

        $this->body = HTTPRequestBody::buildFromRequest();

        $this->etag = $this->headers["If-None-Match"] ?? null;

        $headers = array_keys(array_change_key_case($this->headers, CASE_LOWER));
        if (
            $this->method === "OPTIONS"
            && in_array("access-control-request-method", $headers)
            && in_array("origin", $headers)
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
