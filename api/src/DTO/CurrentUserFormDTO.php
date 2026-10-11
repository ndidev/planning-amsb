<?php

// Path: api/src/DTO/CurrentUserFormDTO.php

declare(strict_types=1);

namespace App\DTO;

use App\Core\Validation\Constraints\Required;
use App\Core\Validation\Constraints\ValidEmailAddress;
use App\Core\Validation\Validation;
use App\Core\Validation\ValidatorTrait;

final class CurrentUserFormDTO implements Validation
{
    use ValidatorTrait;

    public string $uid = '';

    #[Required('Le nom est requis')]
    public string $name = '';

    #[Required("L'adresse e-mail est requise")]
    #[ValidEmailAddress(message: "L'adresse e-mail est invalide")]
    public string $email = '';

    public ?string $password = null;

    public ?string $passwordHash {
        get => $this->password ? \password_hash($this->password, PASSWORD_DEFAULT) : null;
    }
}
