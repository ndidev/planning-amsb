<?php

namespace Api\Utils\Exceptions\Auth;

/**
 * Exception générique entrainant une réponse 403 Forbidden.
 */
class ForbiddenException extends AuthException
{
  private const DEFAULT_MESSAGE = "Accès interdit";
  private const HTTP_STATUS = 403;

  public function __construct(string $message = self::DEFAULT_MESSAGE)
  {
    parent::__construct($message, self::HTTP_STATUS);
  }
}
