<?php

namespace Api\Utils;

use Api\Utils\Database\MySQL;
use Api\Utils\Database\Redis;

/**
 * Classe servant de base aux modèles.
 * 
 * @package Api\Utils
 */
abstract class BaseModel
{
  /**
   * Connexion à la base MariaDB.
   */
  protected MySQL $mysql;

  /**
   * Instance Redis.
   */
  protected Redis $redis;

  public function __construct()
  {
    $this->mysql = new MySQL();
    $this->redis = new Redis();
  }
}
