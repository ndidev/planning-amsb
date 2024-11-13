<?php

// Path: api/tests/Core/Component/CargoOperationTest.php

declare(strict_types=1);

namespace App\Tests\Core\Component;

use App\Core\Component\CargoOperation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CargoOperation::class)]
final class CargoOperationTest extends TestCase
{
    public function testTryFromReturnsNullWhenNullIsGiven(): void
    {
        // Given
        $temptativeOperationName = null;

        // When
        $actual = CargoOperation::tryFrom($temptativeOperationName);

        // Then
        $this->assertNull($actual);
    }

    public function testTryFromReturnsNullWhenEmptyStringIsGiven(): void
    {
        // Given
        $temptativeOperationName = '';

        // When
        $actual = CargoOperation::tryFrom($temptativeOperationName);

        // Then
        $this->assertNull($actual);
    }

    public function testTryFromReturnsNullWhenUnknownOperationNameIsGiven(): void
    {
        // Given
        $temptativeOperationName = 'unknown';

        // When
        $actual = CargoOperation::tryFrom($temptativeOperationName);

        // Then
        $this->assertNull($actual);
    }

    public function testTryFromReturnsImportWhenImportIsGiven(): void
    {
        // Given
        $temptativeOperationName = 'import';

        // When
        $actual = CargoOperation::tryFrom($temptativeOperationName);

        // Then
        $this->assertEquals(CargoOperation::IMPORT, $actual);
    }

    public function testTryFromReturnsExportWhenExportIsGiven(): void
    {
        // Given
        $temptativeOperationName = 'export';

        // When
        $actual = CargoOperation::tryFrom($temptativeOperationName);

        // Then
        $this->assertEquals(CargoOperation::EXPORT, $actual);
    }
}
