<?php

namespace App\Core\Exceptions\Client\Auth;

/**
 * Exception lancée lorsque le compte de l'utilisateur n'est pas actif.
 */
class AccountStatusException extends ForbiddenException
{
    private const DEFAULT_MESSAGE = "Le compte n'est pas actif";

    public function __construct(private string $statut = "", string $message = self::DEFAULT_MESSAGE)
    {
        if ($message === self::DEFAULT_MESSAGE && $statut) {
            $message = "Le compte n'est pas actif. Statut : $statut";
        }

        parent::__construct($message);
    }

    public function getStatut()
    {
        return $this->statut;
    }
}
