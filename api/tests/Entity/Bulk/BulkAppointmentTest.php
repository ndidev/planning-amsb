<?php

// Path: api/tests/Entity/Bulk/BulkAppointmentTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Entity\Bulk\BulkQuantity;
use App\Entity\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkAppointment::class)]
#[UsesClass(BulkProduct::class)]
#[UsesClass(BulkQuality::class)]
#[UsesClass(BulkQuantity::class)]
#[UsesClass(ThirdParty::class)]
class BulkAppointmentTest extends TestCase
{
    public function testInstanciation(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();

        // Then
        $this->assertInstanceOf(BulkAppointment::class, $bulkAppointment);
    }

    public function testSetAndGetDate(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $date = new \DateTimeImmutable('2023-10-01');

        // When
        $bulkAppointment->setDate($date);
        $actualDate = $bulkAppointment->getDate();

        // Then
        $this->assertEquals($date, $bulkAppointment->getDate(), 'The date is not the expected one.');
        $this->assertEquals('2023-10-01', $bulkAppointment->getSqlDate(), 'The SQL date is not the expected one.');
    }

    public function testSetAndGetTime(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $time = new \DateTimeImmutable('10:00');

        // When
        $bulkAppointment->setTime($time);

        // Then
        $this->assertEquals($time, $bulkAppointment->getTime(), 'The time is not the expected one.');
        $this->assertEquals('10:00', $bulkAppointment->getSqlTime(), 'The SQL time is not the expected one.');
    }

    public function testSetAndGetProduct(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $product = new BulkProduct();

        // When
        $bulkAppointment->setProduct($product);

        // Then
        $this->assertSame($product, $bulkAppointment->getProduct());
    }

    public function testSetAndGetQuality(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $quality = new BulkQuality();

        // When
        $bulkAppointment->setQuality($quality);

        // Then
        $this->assertSame($quality, $bulkAppointment->getQuality());
    }

    public function testSetAndGetQuantity(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $value = 100;
        $max = true;
        $quantity = (new BulkQuantity())->setValue($value)->setMax($max);

        // When
        $bulkAppointment->setQuantity($value, $max);

        // Then
        $this->assertEquals($quantity, $bulkAppointment->getQuantity());
    }

    public function testSetAndIsReady(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();

        // When
        $bulkAppointment->setReady(true);

        // Then
        $this->assertTrue($bulkAppointment->isReady());
    }

    public function testSetAndGetSupplier(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $supplier = new ThirdParty();

        // When
        $bulkAppointment->setSupplier($supplier);

        // Then
        $this->assertSame($supplier, $bulkAppointment->getSupplier());
    }

    public function testSetAndGetClient(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $client = new ThirdParty();

        // When
        $bulkAppointment->setCustomer($client);

        // Then
        $this->assertSame($client, $bulkAppointment->getCustomer());
    }

    public function testSetAndGetCarrier(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $carrier = new ThirdParty();

        // When
        $bulkAppointment->setCarrier($carrier);

        // Then
        $this->assertSame($carrier, $bulkAppointment->getCarrier());
    }

    public function testSetAndGetOrderNumber(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $orderNumber = 'ORD123456';

        // When
        $bulkAppointment->setOrderNumber($orderNumber);

        // Then
        $this->assertEquals($orderNumber, $bulkAppointment->getOrderNumber());
    }

    public function testSetAndGetComments(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $comments = 'This is a comment.';

        // When
        $bulkAppointment->setComments($comments);

        // Then
        $this->assertEquals($comments, $bulkAppointment->getComments());
    }

    public function testToArray(): void
    {
        // Given
        $bulkAppointment = new BulkAppointment();
        $bulkAppointment->setDate('2023-10-01')
            ->setTime('10:00')
            ->setOrderNumber('ORD123456')
            ->setComments('This is a comment.')
            ->setReady(true);

        // When
        $array = $bulkAppointment->toArray();

        // Then
        $this->assertEquals('2023-10-01', $array['date_rdv']);
        $this->assertEquals('10:00', $array['heure']);
        $this->assertEquals('ORD123456', $array['num_commande']);
        $this->assertEquals('This is a comment.', $array['commentaire']);
        $this->assertTrue($array['commande_prete']);
    }
}
