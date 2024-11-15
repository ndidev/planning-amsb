<?php

// Path: api/src/Core/HTTP/HTTPRequestBody.php

declare(strict_types=1);

namespace App\Core\HTTP;

use App\Core\Array\ArrayHandler;

/**
 * Class to handle request body parameters.
 */
final class HTTPRequestBody extends ArrayHandler
{
    public function __construct()
    {
        $body = !empty($_POST)
            ? $_POST
            : (array) \json_decode(\file_get_contents("php://input") ?: '', true);

        $this->store = $body;
    }
}
