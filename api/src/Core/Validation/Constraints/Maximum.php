<?php

// Path: api/src/Core/Validation/Constraints/Maximum.php

declare(strict_types=1);

namespace App\Core\Validation\Constraints;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Maximum implements Constraint
{
    public function __construct(
        private int|float $maximum,
        private string $message
    ) {}

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isValid(mixed $value): bool
    {
        if (null === $value) {
            return true;
        }

        return \is_numeric($value) && $value <= $this->maximum;
    }
}
