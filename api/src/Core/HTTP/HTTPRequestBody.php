<?php

// Path: api/src/Core/HTTP/HTTPRequestBody.php

namespace App\Core\HTTP;

/**
 * Class to handle request body parameters.
 * 
 * @phpstan-import-type QueryArray from RequestParameterStore
 */
final class HTTPRequestBody extends RequestParameterStore
{
    public static function buildFromRequest(): self
    {
        $body = !empty($_POST)
            ? $_POST
            : (array) json_decode(file_get_contents("php://input") ?: '', true);

        return new self($body);
    }

    /**
     * @phpstan-param QueryArray $rawBody
     */
    private function __construct(array $rawBody)
    {
        parent::__construct($rawBody);
    }

    public function isEmpty(): bool
    {
        return empty($this->parameterBag);
    }
}
