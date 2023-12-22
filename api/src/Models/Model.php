<?php

namespace App\Models;

use App\Core\Database\MySQL;
use App\Core\Database\Redis;

/**
 * Classe servant de base aux modèles.
 * 
 * @package App\Core
 */
abstract class Model
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
