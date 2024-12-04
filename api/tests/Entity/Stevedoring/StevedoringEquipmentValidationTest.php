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
        $stevedoringEquipment->setType('');

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringEquipment->validate();
    }

    public function testExpectValidationExceptionWhenBrandIsEmpty(): void
    {
        // Given
        $stevedoringEquipment = self::makeValidEquipment();
        $stevedoringEquipment->setBrand('');

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringEquipment->validate();
    }

    public function testExpectValidationExceptionWhenModelIsEmpty(): void
    {
        // Given
        $stevedoringEquipment = self::makeValidEquipment();
        $stevedoringEquipment->setModel('');

        // Then
        $this->expectException(ValidationException::class);

        // When
        $stevedoringEquipment->validate();
    }

    private static function makeValidEquipment(): StevedoringEquipment
    {
        return (new StevedoringEquipment())
            ->setType('Grue')
            ->setBrand('Liebherr')
            ->setModel('LTM 11200-9.1');
    }
}
