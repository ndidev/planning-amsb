<?php

// Path: api/src/Core/Validation/Constraints/PositiveOrNullNumber.php

declare(strict_types=1);

namespace App\Core\Validation\Constraints;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class PositiveOrNullNumber implements Constraint
{
    public function __construct(private string $message) {}

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isValid(mixed $value): bool
    {
        return \is_numeric($value) && $value >= 0;
    }
}
