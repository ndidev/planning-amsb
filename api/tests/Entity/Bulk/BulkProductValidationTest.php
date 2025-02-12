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
        $product->name = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $product->validate();
    }

    public function testExpectNoValidationExceptionWhenColorIsEmpty(): void
    {
        // Given
        $product = $this->makeValidProduct();
        $product->color = '';

        // When
        $product->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExpectNoValidationExceptionWhenUnitIsEmpty(): void
    {
        // Given
        $product = $this->makeValidProduct();
        $product->unit = '';

        // When
        $product->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    private function makeValidProduct(): BulkProduct
    {
        $product = new BulkProduct();
        $product->name = 'Test Product';
        $product->color = 'Red';
        $product->unit = 'kg';

        return $product;
    }
}
