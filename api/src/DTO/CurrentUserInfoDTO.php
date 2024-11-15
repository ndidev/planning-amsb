<?php

// Path: api/src/DTO/CurrentUserInfoDTO.php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\UserAccount;

class CurrentUserInfoDTO implements \JsonSerializable
{
    public function __construct(private UserAccount $user) {}

    public function jsonSerialize(): mixed
    {
        return [
            "uid" => $this->user->getUid(),
            "login" => $this->user->getLogin(),
            "nom" => $this->user->getName(),
            "roles" => $this->user->getRoles()->toArray(),
            "statut" => $this->user->getStatus(),
        ];
    }
}
