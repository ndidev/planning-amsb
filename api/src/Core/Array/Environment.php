<?php

// Path: api/src/Core/Array/Environment.php

declare(strict_types=1);

namespace App\Core\Array;

/**
 * Environment variables management.
 */
final class Environment extends StaticArrayHandler
{
    protected static ?ArrayHandler $envInstance = null;

    protected static function getInstance(): ArrayHandler
    {
        if (null === static::$envInstance) {
            static::$envInstance = new ArrayHandler($_ENV);
        }

        return static::$envInstance;
    }
}
