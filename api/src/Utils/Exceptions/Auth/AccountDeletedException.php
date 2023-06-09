<?php

namespace Api\Utils\Exceptions\Auth;

use Api\Utils\Auth\AccountStatus;

/**
 * Exception lancée lorsque le compte de l'utilisateur est supprimé.
 */
class AccountDeletedException extends AccountStatusException
{
  private const DEFAULT_MESSAGE = "Le compte est supprimé";

  public function __construct(string $message = self::DEFAULT_MESSAGE)
  {
    parent::__construct($this->getStatut(), $message);
  }

  public function getStatut()
  {
    return AccountStatus::DELETED->value;
  }
}
