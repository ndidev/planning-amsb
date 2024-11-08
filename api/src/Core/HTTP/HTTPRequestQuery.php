<?php

// Path: api/src/Core/HTTP/HTTPRequestQuery.php

namespace App\Core\HTTP;

/**
 * Class to handle request query parameters (after "?").
 * 
 * @phpstan-import-type QueryArray from RequestParameterStore
 */
final class HTTPRequestQuery extends RequestParameterStore
{
    public static function buildFromRequest(): self
    {
        $query = [];

        if (!empty($_GET)) {
            $query = $_GET;
        } else {
            /** @var string|false */
            $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

            if (is_string($queryString)) {
                parse_str($queryString, $query);
            }
        }

        return new self($query);
    }

    /**
     * @phpstan-param QueryArray $query
     */
    private function __construct(array $query)
    {
        parent::__construct($query);
    }
}
