<?php

// Path: api/src/Core/Validation/Constraints/MandatoryProperty.php

declare(strict_types=1);

namespace App\Core\Validation\Constraints;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Required implements Constraint
{
    public function __construct(private string $message) {}

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isValid(mixed $value): bool
    {
        return !empty($value);
    }
}
