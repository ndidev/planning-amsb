<?php

namespace Api\Utils;

use Api\Utils\Exceptions\DB\DBConnectionException;

/**
 * Connexion à la base de données.
 */
class DatabaseConnector
{
  private $db_connection = null;

  public function __construct(string $database = null)
  {
    $host = $_ENV["DB_HOST"];
    $port = $_ENV["DB_PORT"];
    $base = $database ?? $_ENV["DB_BASE"];
    $user = $_ENV["DB_USER"];
    $pass = $_ENV["DB_PASS"];

    try {
      $this->db_connection = new \PDO(
        "mysql:host=$host;port=$port;dbname=$base;charset=utf8mb4",
        $user,
        $pass,
        [
          \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
          \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
          \PDO::MYSQL_ATTR_FOUND_ROWS => true,
          \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]
      );
    } catch (\PDOException $pdo_exception) {
      $e = new DBConnectionException(previous: $pdo_exception);
      error_logger($e);
      (new HTTPResponse($e->http_status))
        ->setType("json")
        ->setBody(json_encode([
          "message" => $e->getMessage()
        ]))
        ->send();
    }
  }

  public function getConnection(): \PDO
  {
    return $this->db_connection;
  }

  public function __destruct()
  {
    $this->db_connection = null;
  }
}
