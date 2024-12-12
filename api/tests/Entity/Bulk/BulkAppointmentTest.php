<?php

// Path: api/tests/Entity/Bulk/BulkAppointmentTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkDispatchItem;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\ThirdParty;
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
        $bulkAppointment->setDate($date);
        $actualDate = $bulkAppointment->getDate();
        $actualSqlDate = $bulkAppointment->getSqlDate();

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
        $bulkAppointment->setTime($time);
        $actualTime = $bulkAppointment->getTime();
        $actualSqlTime = $bulkAppointment->getSqlTime();

        // Then
        $this->assertEquals($time, $actualTime, 'The time is not the expected one.');
        $this->assertEquals('10:00', $actualSqlTime, 'The SQL time is not the expected one.');
    }

    public function testSetAndGetProduct(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $product = new BulkProduct();

        // When
        $bulkAppointment->setProduct($product);
        $actualProduct = $bulkAppointment->getProduct();

        // Then
        $this->assertSame($product, $actualProduct);
    }

    public function testSetAndGetQuality(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $quality = new BulkQuality();

        // When
        $bulkAppointment->setQuality($quality);
        $actualQuality = $bulkAppointment->getQuality();

        // Then
        $this->assertSame($quality, $actualQuality);
    }

    public function testSetAndGetQuantityValue(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $quantity = 100;

        // When
        $bulkAppointment->setQuantityValue($quantity);
        $actualQuantity = $bulkAppointment->getQuantityValue();

        // Then
        $this->assertEquals($quantity, $actualQuantity);
    }

    public function testSetAndGetQuantityIsMax(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();

        // When
        $bulkAppointment->setQuantityIsMax(true);
        $actualQuantityIsMax = $bulkAppointment->getQuantityIsMax();

        // Then
        $this->assertTrue($actualQuantityIsMax);
    }

    public function testSetAndIsReady(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();

        // When
        $bulkAppointment->setReady(true);
        $actualIsReady = $bulkAppointment->isReady();

        // Then
        $this->assertTrue($actualIsReady);
    }

    public function testSetAndGetSupplier(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $supplier = new ThirdParty();

        // When
        $bulkAppointment->setSupplier($supplier);
        $actualSupplier = $bulkAppointment->getSupplier();

        // Then
        $this->assertSame($supplier, $actualSupplier);
    }

    public function testSetAndGetClient(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $client = new ThirdParty();

        // When
        $bulkAppointment->setCustomer($client);
        $actualCustomer = $bulkAppointment->getCustomer();

        // Then
        $this->assertSame($client, $actualCustomer);
    }

    public function testSetAndGetCarrier(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $carrier = new ThirdParty();

        // When
        $bulkAppointment->setCarrier($carrier);
        $actualCarrier = $bulkAppointment->getCarrier();

        // Then
        $this->assertSame($carrier, $actualCarrier);
    }

    public function testSetAndGetOrderNumber(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $orderNumber = 'ORD123456';

        // When
        $bulkAppointment->setOrderNumber($orderNumber);
        $actualOrderNumber = $bulkAppointment->getOrderNumber();

        // Then
        $this->assertEquals($orderNumber, $actualOrderNumber);
    }

    public function testSetAndGetPublicComments(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $comments = 'This is a comment.';

        // When
        $bulkAppointment->setPublicComments($comments);
        $actualComments = $bulkAppointment->getPublicComments();

        // Then
        $this->assertEquals($comments, $actualComments);
    }

    public function testSetAndGetPrivateComments(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $comments = 'This is a comment.';

        // When
        $bulkAppointment->setPrivateComments($comments);
        $actualComments = $bulkAppointment->getPrivateComments();

        // Then
        $this->assertEquals($comments, $actualComments);
    }

    public function testSetAndGetOnTv(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();

        // When
        $bulkAppointment->setOnTv(true);
        $actualIsOnTv = $bulkAppointment->isOnTv();

        // Then
        $this->assertTrue($actualIsOnTv);
    }

    public function testSetAndGetArchive(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();

        // When
        $bulkAppointment->setArchive(true);
        $actualIsArchive = $bulkAppointment->isArchive();

        // Then
        $this->assertTrue($actualIsArchive);
    }

    public function testSetAndGetDispatch(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $dispatch = \array_fill(0, 3, new BulkDispatchItem());

        // When
        $bulkAppointment->setDispatch($dispatch);
        $actualIsDispatch = $bulkAppointment->getDispatch();

        // Then
        $this->assertSame($dispatch, $actualIsDispatch);
    }

    public function testToArray(): void
    {
        // Given
        $bulkAppointment = (new BulkAppointment())
            ->setId(1)
            ->setDate('2023-10-01')
            ->setTime('10:00')
            ->setProduct((new BulkProduct())->setId(10))
            ->setQuality((new BulkQuality())->setId(20))
            ->setQuantityValue(100)
            ->setQuantityIsMax(true)
            ->setReady(true)
            ->setSupplier((new ThirdParty())->setId(30))
            ->setCustomer((new ThirdParty())->setId(40))
            ->setCarrier((new ThirdParty())->setId(50))
            ->setOrderNumber('ORD123456')
            ->setPublicComments('This is a public comment.')
            ->setPrivateComments('This is a private comment.')
            ->setOnTv(true)
            ->setArchive(true)
            ->setDispatch(
                array_map(function (array $item) {
                    return (new BulkDispatchItem())
                        ->setStaff((new StevedoringStaff())->setId($item[0]))
                        ->setDate('2023-10-01')
                        ->setRemarks($item[1]);
                }, [[1, "A"], [2, "B"], [3, "C"]])
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
