<?php

namespace Api\Utils\Exceptions\Auth;

use Api\Utils\AccountStatus;

/**
 * Exception lancée lorsque le compte de l'utilisateur n'est pas encore activé.
 */
class AccountPendingException extends AccountStatusException
{
  private const DEFAULT_MESSAGE = "Le compte n'est pas activé";

  public function __construct(string $message = self::DEFAULT_MESSAGE)
  {
    parent::__construct($this->getStatut(), $message);
  }

  public function getStatut()
  {
    return AccountStatus::PENDING->value;
  }
}
