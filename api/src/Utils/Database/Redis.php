<?php

namespace Api\Utils\Database;

use Api\Utils\Exceptions\DB\DBConnectionException;

/**
 * Connexion à la base de données Redis.
 */
class Redis extends \Redis
{
  public function __construct()
  {
    try {
      parent::__construct();
      $this->pconnect($_ENV["REDIS_HOST"], $_ENV["REDIS_PORT"]);
      $this->ping(); // Vérifier la connexion à la base Redis
    } catch (\RedisException $redis_exception) {
      throw new DBConnectionException(previous: $redis_exception);
    }
  }

  public function __destruct()
  {
    $this->close();
  }
}
