<?php

namespace App\Core\HTTP;

class HTTPRequest
{

    /**
     * HTTP request method.
     */
    public string $method;

    /**
     * HTTP request headers.
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
    public array $query = [];

    /**
     * Request body.
     */
    public array $body;

    /**
     * `true` if the request is a CORS preflight request.
     */
    public readonly bool $isPreflight;

    public function __construct()
    {
        $this->method = $_SERVER["REQUEST_METHOD"];

        $this->headers = getallheaders();

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
}
