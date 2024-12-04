<?php

// Path: api/src/Core/Validation/ValidationResult.php

declare(strict_types=1);

namespace App\Core\Validation;

/**
 * This class represents the result of a validation.
 */
class ValidationResult implements \Stringable
{
    public function __construct(
        private bool $hasErrors = false,
        private string $errorMessage = ''
    ) {
        $this->hasErrors = $hasErrors;
        $this->errorMessage = $errorMessage;
    }

    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }

    public function addError(string $errorMessage): void
    {
        if ('' === $errorMessage) {
            return;
        }
        $this->hasErrors = true;

        $this->errorMessage = trim($this->errorMessage . PHP_EOL . $errorMessage);
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function merge(ValidationResult $validationResult): void
    {
        $this->hasErrors = $this->hasErrors || $validationResult->hasErrors();
        $this->errorMessage = trim($this->errorMessage . PHP_EOL . $validationResult->getErrorMessage());
    }

    public function __toString(): string
    {
        return $this->errorMessage;
    }
}
