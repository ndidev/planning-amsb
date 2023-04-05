<?php

namespace Api\Utils\Exceptions;

use Api\Utils\Exceptions\GenericException;

/**
 * Exception de mauvaise requête client.
 */
class ClientException extends GenericException
{
  private const DEFAULT_MESSAGE = "Erreur de requête";
  private const HTTP_STATUS = 400;

  public function __construct(
    string $message = self::DEFAULT_MESSAGE,
    public int $http_status = self::HTTP_STATUS,
    \Throwable|null $previous = null
  ) {
    parent::__construct($message, $http_status, $previous);
  }
}
