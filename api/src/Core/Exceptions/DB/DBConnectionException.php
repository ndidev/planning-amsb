<?php

namespace App\Core\Exceptions\DB;

/**
 * Exception en cas d'erreur de connexion à la base de données.
 */
class DBConnectionException extends DBException
{
  private const DEFAULT_MESSAGE = "Erreur de connexion à la base de données";
  private const HTTP_STATUS = 500;

  public function __construct(
    string $message = self::DEFAULT_MESSAGE,
    \Throwable|null $previous = null
  ) {
    parent::__construct($message, self::HTTP_STATUS, $previous);
  }
}
