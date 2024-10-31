<?php

// Path: api/src/DTO/CurrentUserInfoDTO.php

namespace App\DTO;

use App\Entity\User;

class CurrentUserInfoDTO implements \JsonSerializable
{
    public function __construct(private User $user) {}

    public function jsonSerialize(): mixed
    {
        return [
            "uid" => $this->user->getUid(),
            "login" => $this->user->getLogin(),
            "nom" => $this->user->getName(),
            "roles" => $this->user->getRoles(),
            "statut" => $this->user->getStatus(),
        ];
    }
}
