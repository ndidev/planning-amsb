<?php

// Path: api/src/DTO/CurrentUserDTO.php

declare(strict_types=1);

namespace App\DTO;

use App\Core\Validation\Constraints\Required;
use App\Core\Validation\Validation;
use App\Core\Validation\ValidatorTrait;

class CurrentUserFormDTO implements Validation
{
    use ValidatorTrait;

    public string $uid = '';

    #[Required('Le nom est requis.')]
    public string $name = '';

    public ?string $password = null;

    public ?string $passwordHash {
        get => $this->password ? \password_hash($this->password, PASSWORD_DEFAULT) : null;
    }
}
