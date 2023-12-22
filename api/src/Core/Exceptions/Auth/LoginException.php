<?php

namespace App\Core\Exceptions\Auth;

/**
 * Exception lancée lors d'une erreur de connexion de l'utilisateur.
 */
class LoginException extends UnauthorizedException
{
  private const DEFAULT_MESSAGE = "Utilisateur ou mot de passe incorrect";

  public function __construct(string $message = self::DEFAULT_MESSAGE)
  {
    parent::__construct($message);
  }
}
