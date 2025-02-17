<?php

// Path: api/tests/Entity/Bulk/BulkDispatchItemTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkDispatchItem;
use App\Entity\Stevedoring\StevedoringStaff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkDispatchItem::class)]
#[UsesClass(BulkAppointment::class)]
#[UsesClass(StevedoringStaff::class)]
class BulkDispatchItemTest extends TestCase
{
    public function testSetAndGetDateFromDateTimeImmutable(): void
    {
        // Given
        $bulkDispatchItem = new BulkDispatchItem();
        $date = new \DateTimeImmutable();

        // When
        $bulkDispatchItem->date = $date;
        $actualDate = $bulkDispatchItem->date;

        // Then
        $this->assertEquals($date, $actualDate);
    }

    public function testSetAndGetDateFromString(): void
    {
        // Given
        $bulkDispatchItem = new BulkDispatchItem();
        $date = '2023-10-01';
        $expectedDate = new \DateTimeImmutable($date);

        // When
        $bulkDispatchItem->date = $date;
        $actualDate = $bulkDispatchItem->date;

        // Then
        $this->assertEquals($expectedDate, $actualDate);
    }

    public function testToArray(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $bulkAppointment->id = 1;
        $stevedoringStaff = new StevedoringStaff();
        $stevedoringStaff->id = 2;

        $bulkDispatchItem = new BulkDispatchItem();
        $bulkDispatchItem->appointment = $bulkAppointment;
        $bulkDispatchItem->staff = $stevedoringStaff;
        $bulkDispatchItem->date = '2023-10-01';
        $bulkDispatchItem->remarks = 'Remarks';

        $expectedArray = [
            'appointmentId' => 1,
            'staffId' => 2,
            'date' => '2023-10-01',
            'remarks' => 'Remarks',
        ];

        // When
        $actualArray = $bulkDispatchItem->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray, 'The array is not the expected one.');
    }
}
