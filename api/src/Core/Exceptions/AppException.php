<?php

namespace App\Core\Exceptions;

/**
 * Exception générique de l'application.
 * 
 * Toutes les erreurs lancées depuis l'application doivent dériver de cette classe.
 */
abstract class AppException extends \Exception
{
  private const DEFAULT_MESSAGE = "Erreur générique de l'application";
  private const HTTP_STATUS = 500;

  public function __construct(
    string $message = self::DEFAULT_MESSAGE,
    public int $http_status = self::HTTP_STATUS,
    \Throwable|null $previous = null
  ) {
    parent::__construct($message, 0, $previous);
  }
}
