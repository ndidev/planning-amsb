<?php

// Path: api/src/Core/Component/Server.php

declare(strict_types=1);

namespace App\Core\Array;

/**
 * Server variables management.
 */
final class Server extends StaticArrayHandler
{
    protected static ?ArrayHandler $serverInstance = null;

    protected static function getInstance(): ArrayHandler
    {
        if (null === static::$serverInstance) {
            static::$serverInstance = new ArrayHandler($_SERVER);
        }

        return static::$serverInstance;
    }
}
