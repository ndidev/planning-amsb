<?php

// Path: api/tests/DTO/SupplierWithUniqueDeliveryNoteNumberTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\SupplierWithUniqueDeliveryNoteNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SupplierWithUniqueDeliveryNoteNumber::class)]
final class SupplierWithUniqueDeliveryNoteNumberTest extends TestCase
{
    public function testSetAndGetId(): void
    {
        // Given
        $supplierWithUniqueDeliveryNoteNumber = new SupplierWithUniqueDeliveryNoteNumber();
        $id = 1;

        // When
        $supplierWithUniqueDeliveryNoteNumber->setId($id);

        // Then
        self::assertSame($id, $supplierWithUniqueDeliveryNoteNumber->getId());
    }

    public function testSetAndGetRegexp(): void
    {
        // Given
        $supplierWithUniqueDeliveryNoteNumber = new SupplierWithUniqueDeliveryNoteNumber();
        $regexp = 'regexp';

        // When
        $supplierWithUniqueDeliveryNoteNumber->setRegexp($regexp);

        // Then
        self::assertSame($regexp, $supplierWithUniqueDeliveryNoteNumber->getRegexp());
    }
}
