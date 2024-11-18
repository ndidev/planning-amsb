<?php

// Path: api/src/Core/Interfaces/Validation.php

declare(strict_types=1);

namespace App\Core\Validation;

use App\Core\Exceptions\Client\ValidationException;

/**
 * This interface defines a validate() method that validates an object.
 */
interface Validation
{
    /**
     * Validate the object.
     * 
     * @param bool $throw Throw an exception if the validation fails. Default is `true`.
     * 
     * @return ValidationResult 
     * 
     * @throws ValidationException 
     */
    public function validate(bool $throw): ValidationResult;
}
