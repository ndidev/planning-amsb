<?php

// Path: api/src/Core/HTTP/HTTPRequestQuery.php

declare(strict_types=1);

namespace App\Core\HTTP;

use App\Core\Array\ArrayHandler;
use App\Core\Array\Server;

/**
 * Class to handle request query parameters (after "?").
 */
final class HTTPRequestQuery extends ArrayHandler
{
    public function __construct()
    {
        $query = [];

        if (!empty($_GET)) {
            $query = $_GET;
        } else {
            /** @var string|false */
            $queryString = \parse_url(Server::getString('REQUEST_URI'), PHP_URL_QUERY);

            if (\is_string($queryString)) {
                \parse_str($queryString, $query);
            }
        }

        $this->store = $query;
    }
}
