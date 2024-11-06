<?php

namespace App\Core\HTTP;

use App\Core\Exceptions\Client\ClientException;

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
     * @var array<string, mixed>
     */
    private array $body;

    /**
     * `true` if the request is a CORS preflight request.
     */
    public readonly bool $isPreflight;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER["REQUEST_METHOD"]);

        if (function_exists('getallheaders')) {
            $this->headers = getallheaders();
        }

        $url = parse_url($_SERVER['REQUEST_URI']);
        $this->path = $url["path"] ?? '';

        $query = [];
        if (!empty($_GET)) {
            $query = $_GET;
        } else {
            parse_str($url["query"] ?? '', $query);
        }

        $this->query = new HTTPRequestQuery($query);

        $this->body = !empty($_POST) ? $_POST : (array) json_decode(file_get_contents("php://input") ?: '', true);

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
     * @param bool $allowEmpty Allow an empty body.
     * 
     * @return array<string, mixed> Request body.
     * 
     * @throws ClientException If the body is empty and `$allowEmpty` is `false`.
     */
    public function getBody(bool $allowEmpty = false): array
    {
        if (!$allowEmpty && empty($this->body)) {
            throw new ClientException("Le corps de la requête est vide.");
        }

        return $this->body;
    }
}
