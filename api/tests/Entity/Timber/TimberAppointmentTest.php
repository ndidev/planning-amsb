<?php

// Path: api/tests/Entity/Timber/TimberAppointmentTest.php

namespace App\Tests\Entity\Timber;

use App\Entity\ThirdParty;
use App\Entity\Timber\TimberAppointment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberAppointment::class)]
#[UsesClass(ThirdParty::class)]
class TimberAppointmentTest extends TestCase
{
    public function testInstanciation(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // Then
        $this->assertInstanceOf(TimberAppointment::class, $appointment);
    }

    public function testSetOnHold(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->setOnHold(true);

        // Then
        $this->assertTrue($appointment->isOnHold());
    }

    public function testSetDateWithString(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $dateString = '2023-10-01';

        // When
        $appointment->setDate($dateString);

        // Then
        $this->assertEquals($dateString, $appointment->getDate(true));
    }

    public function testSetDateWithDateTimeImmutable(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $date = new \DateTimeImmutable('2023-10-01');

        // When
        $appointment->setDate($date);

        // Then
        $this->assertEquals($date, $appointment->getDate());
    }

    public function testSetArrivalTimeWithString(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $timeString = '08:00';

        // When
        $appointment->setArrivalTime($timeString);

        // Then
        $this->assertEquals($timeString, $appointment->getArrivalTime(true));
    }

    public function testSetArrivalTimeWithDateTimeImmutable(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $time = new \DateTimeImmutable('08:00');

        // When
        $appointment->setArrivalTime($time);

        // Then
        $this->assertEquals($time, $appointment->getArrivalTime());
    }

    public function testSetSupplier(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $supplier = $this->createMock(ThirdParty::class);

        // When
        $appointment->setSupplier($supplier);

        // Then
        $this->assertSame($supplier, $appointment->getSupplier());
    }

    public function testSetLoadingPlace(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $loadingPlace = $this->createMock(ThirdParty::class);

        // When
        $appointment->setLoadingPlace($loadingPlace);

        // Then
        $this->assertSame($loadingPlace, $appointment->getLoadingPlace());
    }

    public function testSetDeliveryPlace(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $deliveryPlace = $this->createMock(ThirdParty::class);

        // When
        $appointment->setDeliveryPlace($deliveryPlace);

        // Then
        $this->assertSame($deliveryPlace, $appointment->getDeliveryPlace());
    }

    public function testSetCustomer(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $customer = $this->createMock(ThirdParty::class);

        // When
        $appointment->setCustomer($customer);

        // Then
        $this->assertSame($customer, $appointment->getCustomer());
    }

    public function testSetCarrier(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $carrier = $this->createMock(ThirdParty::class);

        // When
        $appointment->setCarrier($carrier);

        // Then
        $this->assertSame($carrier, $appointment->getCarrier());
    }

    public function testSetTransportBroker(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $transportBroker = $this->createMock(ThirdParty::class);

        // When
        $appointment->setTransportBroker($transportBroker);

        // Then
        $this->assertSame($transportBroker, $appointment->getTransportBroker());
    }

    public function testSetReady(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->setReady(true);

        // Then
        $this->assertTrue($appointment->isReady());
    }

    public function testSetCharteringConfirmationSent(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->setCharteringConfirmationSent(true);

        // Then
        $this->assertTrue($appointment->isCharteringConfirmationSent());
    }

    public function testSetDeliveryNoteNumber(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $deliveryNoteNumber = 'DN12345';

        // When
        $appointment->setDeliveryNoteNumber($deliveryNoteNumber);

        // Then
        $this->assertEquals($deliveryNoteNumber, $appointment->getDeliveryNoteNumber());
    }

    public function testSetPublicComment(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $publicComment = 'This is a public comment.';

        // When
        $appointment->setPublicComment($publicComment);

        // Then
        $this->assertEquals($publicComment, $appointment->getPublicComment());
    }

    public function testSetPrivateComment(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $privateComment = 'This is a private comment.';

        // When
        $appointment->setPrivateComment($privateComment);

        // Then
        $this->assertEquals($privateComment, $appointment->getPrivateComment());
    }

    public function testToArray(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $appointment->setOnHold(true)
            ->setDate('2023-10-01')
            ->setArrivalTime('08:00')
            ->setDepartureTime('10:00')
            ->setDeliveryNoteNumber('DN12345')
            ->setPublicComment('This is a public comment.')
            ->setPrivateComment('This is a private comment.');

        // When
        $array = $appointment->toArray();

        // Then
        $this->assertIsArray($array);
        $this->assertEquals(true, $array['attente']);
        $this->assertEquals('2023-10-01', $array['date_rdv']);
        $this->assertEquals('08:00', $array['heure_arrivee']);
        $this->assertEquals('10:00', $array['heure_depart']);
        $this->assertEquals('DN12345', $array['numero_bl']);
        $this->assertEquals('This is a public comment.', $array['commentaire_public']);
        $this->assertEquals('This is a private comment.', $array['commentaire_cache']);
    }
}
