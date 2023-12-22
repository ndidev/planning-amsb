<?php

namespace App\Core\Exceptions\Auth;

/**
 * Exception générique entrainant une réponse 401 Unauthorized.
 */
class UnauthorizedException extends AuthException
{
  private const DEFAULT_MESSAGE = "Authentification nécessaire";
  private const HTTP_STATUS = 401;

  public function __construct(string $message = self::DEFAULT_MESSAGE)
  {
    parent::__construct($message, self::HTTP_STATUS);
  }
}
