<?php

// Path: api/tests/Entity/Bulk/BulkAppointmentTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkDispatchItem;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\ThirdParty\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkAppointment::class)]
#[UsesClass(BulkProduct::class)]
#[UsesClass(BulkQuality::class)]
#[UsesClass(ThirdParty::class)]
#[UsesClass(BulkDispatchItem::class)]
class BulkAppointmentTest extends TestCase
{
    public function testSetAndGetDate(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $date = new \DateTimeImmutable('2023-10-01');

        // When
        $bulkAppointment->date = $date;
        $actualDate = $bulkAppointment->date;
        $actualSqlDate = $bulkAppointment->sqlDate;

        // Then
        $this->assertEquals($date, $actualDate, 'The date is not the expected one.');
        $this->assertEquals('2023-10-01', $actualSqlDate, 'The SQL date is not the expected one.');
    }

    public function testSetAndGetTime(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $time = new \DateTimeImmutable('10:00');

        // When
        $bulkAppointment->time = $time;
        $actualTime = $bulkAppointment->time;
        $actualSqlTime = $bulkAppointment->sqlTime;

        // Then
        $this->assertEquals($time, $actualTime, 'The time is not the expected one.');
        $this->assertEquals('10:00', $actualSqlTime, 'The SQL time is not the expected one.');
    }

    public function testSetAndGetDispatch(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $dispatch = \array_fill(0, 3, new BulkDispatchItem());

        // When
        $bulkAppointment->dispatch = $dispatch;
        $actualIsDispatch = $bulkAppointment->dispatch;

        // Then
        $this->assertSame($dispatch, $actualIsDispatch);
    }

    public function testToArray(): void
    {
        // Given
        $bulkProduct = new BulkProduct();
        $bulkProduct->id = 10;

        $bulkQuality = new BulkQuality();
        $bulkQuality->id = 20;

        $supplier = new ThirdParty();
        $supplier->id = 30;

        $customer = new ThirdParty();
        $customer->id = 40;

        $carrier = new ThirdParty();
        $carrier->id = 50;

        $bulkAppointment = new BulkAppointment();
        $bulkAppointment->id = 1;
        $bulkAppointment->date = '2023-10-01';
        $bulkAppointment->time = '10:00';
        $bulkAppointment->product = $bulkProduct;
        $bulkAppointment->quality = $bulkQuality;
        $bulkAppointment->quantityValue = 100;
        $bulkAppointment->quantityIsMax = true;
        $bulkAppointment->isReady = true;
        $bulkAppointment->supplier = $supplier;
        $bulkAppointment->customer = $customer;
        $bulkAppointment->carrier = $carrier;
        $bulkAppointment->orderNumber = 'ORD123456';
        $bulkAppointment->publicComments = 'This is a public comment.';
        $bulkAppointment->privateComments = 'This is a private comment.';
        $bulkAppointment->isOnTv = true;
        $bulkAppointment->isArchive = true;
        $bulkAppointment->dispatch = \array_map(
            function ($item) {
                $staff = new StevedoringStaff();
                $staff->id = $item[0];

                $dispatchItem = new BulkDispatchItem();
                $dispatchItem->staff = $staff;
                $dispatchItem->date = '2023-10-01';
                $dispatchItem->remarks = $item[1];

                return $dispatchItem;
            },
            [[1, "A"], [2, "B"], [3, "C"]]
        );

        $expectedArray = [
            'id' => 1,
            'date_rdv' => '2023-10-01',
            'heure' => '10:00',
            'produit' => 10,
            'qualite' => 20,
            'quantite' => 100,
            'max' => true,
            'commande_prete' => true,
            'fournisseur' => 30,
            'client' => 40,
            'transporteur' => 50,
            'num_commande' => 'ORD123456',
            'commentaire_public' => 'This is a public comment.',
            'commentaire_prive' => 'This is a private comment.',
            'showOnTv' => true,
            'archive' => true,
            'dispatch' => [
                [
                    'appointmentId' => 1,
                    'staffId' => 1,
                    'date' => '2023-10-01',
                    'remarks' => 'A'
                ],
                [
                    'appointmentId' => 1,
                    'staffId' => 2,
                    'date' => '2023-10-01',
                    'remarks' => 'B'
                ],
                [
                    'appointmentId' => 1,
                    'staffId' => 3,
                    'date' => '2023-10-01',
                    'remarks' => 'C'
                ],
            ],
        ];

        // When
        $actualArray = $bulkAppointment->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
