<?php

// Path: api/src/DTO/CurrentUserInfoDTO.php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User;

final readonly class CurrentUserInfoDTO implements \JsonSerializable
{
    public function __construct(private User $user) {}

    public function jsonSerialize(): mixed
    {
        return [
            "uid" => $this->user->uid,
            "login" => $this->user->login,
            "nom" => $this->user->name,
            "roles" => $this->user->roles->toArray(),
            "statut" => $this->user->status,
        ];
    }
}
