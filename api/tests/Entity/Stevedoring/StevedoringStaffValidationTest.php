<?php

// Path: api/tests/Entity/Stevedoring/StevedoringStaffValidationTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Stevedoring;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\Stevedoring\StevedoringStaff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StevedoringStaff::class)]
final class StevedoringStaffValidationTest extends TestCase
{
    public function testExpectValidationExceptionOnNewStaff(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringStaff->validate();
    }

    public function testNoExceptionOnValidStaff(): void
    {
        // Given
        $stevedoringStaff = self::makeValidStaff();

        // When
        $stevedoringStaff->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExpectValidationExceptionWhenFirstnameIsEmpty(): void
    {
        // Given
        $stevedoringStaff = self::makeValidStaff();
        $stevedoringStaff->firstname = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringStaff->validate();
    }

    public function testExpectValidationExceptionWhenLastnameIsEmpty(): void
    {
        // Given
        $stevedoringStaff = self::makeValidStaff();
        $stevedoringStaff->lastname = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringStaff->validate();
    }

    public function testExpectValidationExceptionWhenTypeIsInvalid(): void
    {
        // Given
        $stevedoringStaff = self::makeValidStaff();
        $stevedoringStaff->type = 'invalid';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringStaff->validate();
    }

    public function testExpectValidationExceptionWhenTypeIsInterimAndAgencyIsEmpty(): void
    {
        // Given
        $stevedoringStaff = self::makeValidStaff();
        $stevedoringStaff->type = 'interim';
        $stevedoringStaff->tempWorkAgency = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringStaff->validate();
    }

    private static function makeValidStaff(): StevedoringStaff
    {
        $stevedoringStaff = new StevedoringStaff();
        $stevedoringStaff->firstname = 'John';
        $stevedoringStaff->lastname = 'Doe';
        $stevedoringStaff->type = 'mensuel';
        $stevedoringStaff->isActive = true;

        return $stevedoringStaff;
    }
}
