<?php

// Path: api/src/Core/Validation/ValidatorTrait.php

declare(strict_types=1);

namespace App\Core\Validation;

use App\Core\Exceptions\Client\ValidationException;
use App\Core\Validation\Constraints\Constraint;

/**
 * This trait implements a `validate()` method that validates the object properties.
 * 
 * @see \App\Core\Validation\Validation
 */
trait ValidatorTrait
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
    public function validate(bool $throw = true): ValidationResult
    {
        $validationResult = new ValidationResult();

        $properties = new \ReflectionClass($this)->getProperties();

        foreach ($properties as $property) {
            $propertValidationResult = $this->validateProperty($property);

            $validationResult->merge($propertValidationResult);

            if (\is_iterable($property->getValue($this))) {
                foreach ($property->getValue($this) as $value) {
                    if ($value instanceof self) {
                        $validationResult->merge($value->validate(false));
                    }
                }
            }
        }

        if ($throw && $validationResult->hasErrors()) {
            throw new ValidationException(errors: $validationResult->getErrorMessage());
        }

        return $validationResult;
    }

    private function validateProperty(\ReflectionProperty $property): ValidationResult
    {
        $validationResult = new ValidationResult();

        $value = $property->getValue($this);
        $attributes = $property->getAttributes();

        foreach ($attributes as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if (! ($attributeInstance instanceof Constraint)) {
                continue;
            }

            $validator = $attributeInstance;

            if (!$validator->isValid($value)) {
                $validationResult->addError($validator->getMessage());
            }
        }

        return $validationResult;
    }
}
