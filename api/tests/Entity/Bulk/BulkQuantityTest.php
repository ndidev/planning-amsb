<?php

// Path: api/tests/Entity/Bulk/BulkQuantityTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use PHPUnit\Framework\TestCase;
use App\Entity\Bulk\BulkQuantity;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BulkQuantity::class)]
class BulkQuantityTest extends TestCase
{

    public function testConstructorInitialValues(): void
    {
        // When
        $bulkQuantity = new BulkQuantity();

        // Then
        $this->assertEquals(0, $bulkQuantity->getValue());
        $this->assertFalse($bulkQuantity->isMax());
    }

    public function testSetValueUpdatesValue(): void
    {
        // Given
        $bulkQuantity = new BulkQuantity();
        $newValue = 20;

        // When
        $bulkQuantity->setValue($newValue);

        // Then
        $this->assertEquals($newValue, $bulkQuantity->getValue());
    }

    public function testSetMaxUpdatesMax(): void
    {
        // Given
        $bulkQuantity = new BulkQuantity();
        $newMax = true;

        // When
        $bulkQuantity->setMax($newMax);

        // Then
        $this->assertTrue($bulkQuantity->isMax());
    }

    public function testSetValueAndMax(): void
    {
        // Given
        $bulkQuantity = new BulkQuantity();
        $newValue = 15;
        $newMax = true;

        // When
        $bulkQuantity->setValue($newValue);
        $bulkQuantity->setMax($newMax);

        // Then
        $this->assertEquals($newValue, $bulkQuantity->getValue());
        $this->assertTrue($bulkQuantity->isMax());
    }

    public function testSetValueDoesNotAffectMax(): void
    {
        // Given
        $bulkQuantity = new BulkQuantity();
        $bulkQuantity->setMax(true);
        $newValue = 30;

        // When
        $bulkQuantity->setValue($newValue);

        // Then
        $this->assertEquals($newValue, $bulkQuantity->getValue());
        $this->assertTrue($bulkQuantity->isMax());
    }

    public function testSetMaxDoesNotAffectValue(): void
    {
        // Given
        $bulkQuantity = new BulkQuantity();
        $bulkQuantity->setValue(25);
        $newMax = true;

        // When
        $bulkQuantity->setMax($newMax);

        // Then
        $this->assertEquals(25, $bulkQuantity->getValue());
        $this->assertTrue($bulkQuantity->isMax());
    }
}
