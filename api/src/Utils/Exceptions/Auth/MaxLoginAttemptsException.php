<?php

namespace Api\Utils\Exceptions\Auth;

/**
 * Exception lancée lorsque le nombre de tentatives de connexions a été atteint.
 */
class MaxLoginAttemptsException extends AccountLockedException
{
  private const DEFAULT_MESSAGE = "Le compte est bloqué : nombre de tentatives de connexions dépassé";

  public function __construct(string $message = self::DEFAULT_MESSAGE)
  {
    parent::__construct($message);
  }
}
