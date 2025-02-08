<?php

// Path: api/tests/Entity/Stevedoring/StevedoringEquipmentValidationTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Stevedoring;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\Stevedoring\StevedoringEquipment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StevedoringEquipment::class)]
final class StevedoringEquipmentValidationTest extends TestCase
{
    public function testExpectValidationExceptionOnNewEquipment(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringEquipment->validate();
    }

    public function testNoExceptionOnValidEquipment(): void
    {
        // Given
        $stevedoringEquipment = self::makeValidEquipment();

        // When
        $stevedoringEquipment->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExpectValidationExceptionWhenTypeIsEmpty(): void
    {
        // Given
        $stevedoringEquipment = self::makeValidEquipment();
        $stevedoringEquipment->type = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringEquipment->validate();
    }

    public function testExpectValidationExceptionWhenBrandIsEmpty(): void
    {
        // Given
        $stevedoringEquipment = self::makeValidEquipment();
        $stevedoringEquipment->brand = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringEquipment->validate();
    }

    public function testExpectValidationExceptionWhenModelIsEmpty(): void
    {
        // Given
        $stevedoringEquipment = self::makeValidEquipment();
        $stevedoringEquipment->model = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringEquipment->validate();
    }

    private static function makeValidEquipment(): StevedoringEquipment
    {
        $stevedoringEquipment = new StevedoringEquipment();
        $stevedoringEquipment->type = 'Grue';
        $stevedoringEquipment->brand = 'Liebherr';
        $stevedoringEquipment->model = 'LTM 11200-9.1';

        return $stevedoringEquipment;
    }
}
