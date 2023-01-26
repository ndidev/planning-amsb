<?php

namespace Api\Utils;

use Api\Utils\DatabaseConnector as DB;
use PDO;
use Redis;

/**
 * Classe servant de base aux modèles.
 * 
 * @package Api\Utils
 */
class BaseModel
{
  /**
   * Connexion à la base MariaDB.
   */
  protected PDO $db;

  /**
   * Instance Redis.
   */
  protected Redis $redis;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
    $this->redis = new Redis();
    $this->redis->connect($_ENV["REDIS_HOST"], $_ENV["REDIS_PORT"]);
  }
}
