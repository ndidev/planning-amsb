<?php

// Path: api/tests/Entity/Bulk/BulkPRoductValidationDTO.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\Bulk\BulkProduct;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkProduct::class)]
class BulkProductValidationTest extends TestCase
{
    public function testExpectValidationExceptionOnNewProduct(): void
    {
        // Given
        $product = new BulkProduct();

        // Then
        $this->expectException(ValidationException::class);

        // When
        $product->validate();
    }

    public function testNoExceptionOnValidProduct(): void
    {
        // Given
        $product = $this->makeValidProduct();

        // When
        $product->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExpectValidationExceptionWhenNameIsEmpty(): void
    {
        // Given
        $product = $this->makeValidProduct();
        $product->setName('');

        // Then
        $this->expectException(ValidationException::class);

        // When
        $product->validate();
    }

    public function testExpectValidationExceptionWhenColorIsEmpty(): void
    {
        // Given
        $product = $this->makeValidProduct();
        $product->setColor('');

        // Then
        $this->expectException(ValidationException::class);

        // When
        $product->validate();
    }

    public function testExpectNoValidationExceptionWhenUnitIsEmpty(): void
    {
        // Given
        $product = $this->makeValidProduct();
        $product->setUnit('');

        // When
        $product->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    private function makeValidProduct(): BulkProduct
    {
        $product = new BulkProduct();
        $product->setName('Test Product');
        $product->setColor('Red');
        $product->setUnit('kg');

        return $product;
    }
}
