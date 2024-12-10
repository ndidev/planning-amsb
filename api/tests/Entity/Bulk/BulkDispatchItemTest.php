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
    public function testSetAndGetAppointment(): void
    {
        // Given
        $bulkDispatchItem = new BulkDispatchItem();
        $bulkAppointment = new BulkAppointment();

        // When
        $bulkDispatchItem->setAppointment($bulkAppointment);
        $actualBulkAppointment = $bulkDispatchItem->getAppointment();

        // Then
        $this->assertSame($bulkAppointment, $actualBulkAppointment);
    }

    public function testSetAndGetStaff(): void
    {
        // Given
        $bulkDispatchItem = new BulkDispatchItem();
        $stevedoringStaff = new StevedoringStaff();

        // When
        $bulkDispatchItem->setStaff($stevedoringStaff);
        $actualStevedoringStaff = $bulkDispatchItem->getStaff();

        // Then
        $this->assertSame($stevedoringStaff, $actualStevedoringStaff);
    }

    public function testSetAndGetDate(): void
    {
        // Given
        $bulkDispatchItem = new BulkDispatchItem();
        $date = new \DateTimeImmutable();

        // When
        $bulkDispatchItem->setDate($date);
        $actualDate = $bulkDispatchItem->getDate();

        // Then
        $this->assertEquals($date, $actualDate);
    }

    public function testSetAndGetRemarks(): void
    {
        // Given
        $bulkDispatchItem = new BulkDispatchItem();
        $remarks = 'Remarks';

        // When
        $bulkDispatchItem->setRemarks($remarks);
        $actualRemarks = $bulkDispatchItem->getRemarks();

        // Then
        $this->assertSame($remarks, $actualRemarks);
    }

    public function testToArray(): void
    {
        // Given
        $bulkDispatchItem = new BulkDispatchItem();
        $bulkAppointmentId = 1;
        $stevedoringStaffId = 2;
        $remarks = 'Remarks';

        $bulkDispatchItem = (new BulkDispatchItem())
            ->setAppointment((new BulkAppointment())->setId($bulkAppointmentId))
            ->setStaff((new StevedoringStaff())->setId($stevedoringStaffId))
            ->setDate('2023-10-01')
            ->setRemarks($remarks);

        $expectedArray = [
            'appointmentId' => $bulkAppointmentId,
            'staffId' => $stevedoringStaffId,
            'date' => '2023-10-01',
            'remarks' => $remarks,
        ];

        // When
        $actualArray = $bulkDispatchItem->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray, 'The array is not the expected one.');
    }
}
