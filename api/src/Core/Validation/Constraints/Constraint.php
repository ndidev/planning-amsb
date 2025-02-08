<?php

// Path: api/src/Core/Validation/Constraints/ValidationAttribute.php

declare(strict_types=1);

namespace App\Core\Validation\Constraints;

interface Constraint
{
    public function getMessage(): string;

    public function isValid(mixed $value): bool;
}
