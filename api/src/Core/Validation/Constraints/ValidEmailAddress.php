<?php

// Path: api/src/Core/Validation/Constraints/ValidEmailAddress.php

declare(strict_types=1);

namespace App\Core\Validation\Constraints;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ValidEmailAddress implements Constraint
{
    public function __construct(private string $message) {}

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isValid(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        if ($value === '') {
            return true;
        }

        $validator = new EmailValidator();
        $validation = new MultipleValidationWithAnd([
            new RFCValidation(),
            // new DNSCheckValidation() // nécessite une résolution DNS, peut ralentir la validation
        ]);
        return $validator->isValid($value, $validation);
    }
}
