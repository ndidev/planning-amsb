<?php

namespace App\Core\HTTP;

use App\Core\Exceptions\Client\ClientException;

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
     * @var array<string, string>
     */
    private array $query = [];

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
        $this->path = $url["path"];
        parse_str($url["query"] ?? "", $this->query);

        $this->body = !empty($_POST) ? $_POST : (array) json_decode(file_get_contents("php://input"), true);

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
     * @return array<string, string> Request query.
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Get a query parameter.
     * 
     * @param string $name       Name of the query parameter.
     * @param mixed  $default    Default value if the query parameter is not set.
     * @param string $type       Type of the query parameter. Default is 'string'.
     * @param bool   $allowEmpty Allow an empty query parameter.
     * 
     * @return mixed Query parameter with the desired type.
     * 
     * @phpstan-return ($type is 'string' ? string : ($type is 'int' ? int : ($type is 'float' ? float : ($type is 'bool' ? bool : mixed))))
     */
    public function getQueryParam(
        string $name,
        mixed $default = null,
        string $type = 'string',
        bool $allowEmpty = false
    ): mixed {
        if (isset($this->query[$name])) {
            $paramValue = $this->query[$name];
        } else {
            $paramValue = $default;
        }

        if (!$allowEmpty && empty($paramValue)) {
            $paramValue = $default;
        }

        // If null is expected as default, return null
        if ($paramValue === null) {
            return null;
        }

        return match ($type) {
            "string" => (string) $paramValue,
            "int" => (int) $paramValue,
            "float" => (float) $paramValue,
            "bool" => (bool) $paramValue,
            "datetime" => new \DateTime($paramValue),
            default => $paramValue,
        };
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
