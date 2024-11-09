<?php

// Path: api/src/Core/Database/Redis.php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Exceptions\Server\DB\DBConnectionException;

/**
 * Connexion à la base de données Redis.
 */
class Redis extends \Redis
{
    public function __construct()
    {
        try {
            parent::__construct();
            $this->pconnect(
                host: $_ENV["REDIS_HOST"],
                port: (int) $_ENV["REDIS_PORT"],
                read_timeout: 1,
            );
            $this->ping(); // Vérifier la connexion à la base Redis
        } catch (\RedisException $redisException) {
            throw new DBConnectionException(previous: $redisException);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
