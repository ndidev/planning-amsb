<?php

namespace App\Core\Exceptions;

use App\Core\Exceptions\AppException;

/**
 * Exception de mauvaise requête client.
 */
class ServerException extends AppException
{
  private const DEFAULT_MESSAGE = "Erreur serveur";
  private const HTTP_STATUS = 500;

  public function __construct(
    string $message = self::DEFAULT_MESSAGE,
    public int $http_status = self::HTTP_STATUS,
    \Throwable|null $previous = null
  ) {
    parent::__construct($message, $http_status, $previous);
  }
}
