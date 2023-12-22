<?php

namespace App\Core\Exceptions\Auth;

/**
 * Exception lancée lorsque la session de l'utilisateur n'existe pas.
 */
class SessionException extends UnauthorizedException
{
  private const DEFAULT_MESSAGE = "La session n'existe pas ou n'est plus active";

  public function __construct(string $message = self::DEFAULT_MESSAGE)
  {
    parent::__construct($message);
  }
}
