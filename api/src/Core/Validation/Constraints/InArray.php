<?php

// Path: api/src/Core/Validation/Constraints/InArray.php

declare(strict_types=1);

namespace App\Core\Validation\Constraints;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class InArray implements Constraint
{
    /**
     * @param array<mixed> $values 
     * @param string       $message 
     */
    public function __construct(
        private array $values,
        private string $message,
    ) {}

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isValid(mixed $value): bool
    {
        return in_array($value, $this->values);
    }
}
