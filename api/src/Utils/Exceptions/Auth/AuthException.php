<?php

namespace Api\Utils\Exceptions\Auth;

use Api\Utils\Exceptions\GenericException;

/**
 * Exception générique d'authentification de l'utilisateur.
 */
class AuthException extends GenericException
{
  private const DEFAULT_MESSAGE = "Erreur d'authentification";
  private const HTTP_STATUS = 500;

  public function __construct(
    string $message = self::DEFAULT_MESSAGE,
    public int $http_status = self::HTTP_STATUS,
    \Throwable|null $previous = null
  ) {
    parent::__construct($message, $http_status, $previous);
  }
}
