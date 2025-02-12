<?php

// Path: api/tests/Entity/Timber/TimberAppointmentTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Timber;

use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\ThirdParty;
use App\Entity\Timber\TimberAppointment;
use App\Entity\Timber\TimberDispatchItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberAppointment::class)]
#[UsesClass(ThirdParty::class)]
#[UsesClass(TimberDispatchItem::class)]
#[UsesClass(StevedoringStaff::class)]
class TimberAppointmentTest extends TestCase
{
    public function testSetAndGetOnHold(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->setOnHold(true);
        $actualOnHold = $appointment->isOnHold();

        // Then
        $this->assertTrue($actualOnHold);
    }

    public function testSetAndGetDateWithString(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $dateString = '2023-10-01';
        $expectedDate = new \DateTimeImmutable($dateString);

        // When
        $appointment->setDate($dateString);
        $actualDate = $appointment->getDate();

        // Then
        $this->assertEquals($expectedDate, $actualDate);
    }

    public function testSetAndGetDateWithDateTimeImmutable(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $date = new \DateTimeImmutable('2023-10-01');

        // When
        $appointment->setDate($date);
        $actualDate = $appointment->getDate();

        // Then
        $this->assertEquals($date, $actualDate);
    }

    public function testGetSqlDate(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $date = new \DateTimeImmutable('2023-10-01');

        // When
        $appointment->setDate($date);
        $sqlDate = $appointment->getSqlDate();

        // Then
        $this->assertEquals('2023-10-01', $sqlDate);
    }

    public function testSetAndGetArrivalTimeWithString(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $timeString = '08:00';
        $expectedTime = new \DateTimeImmutable($timeString);

        // When
        $appointment->setArrivalTime($timeString);
        $actualTime = $appointment->getArrivalTime();

        // Then
        $this->assertEquals($expectedTime, $actualTime);
    }

    public function testSetAndGetArrivalTimeWithDateTimeImmutable(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $time = new \DateTimeImmutable('08:00');

        // When
        $appointment->setArrivalTime($time);
        $actualTime = $appointment->getArrivalTime();

        // Then
        $this->assertEquals($time, $actualTime);
    }

    public function testGetSqlArrivalTime(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $time = new \DateTimeImmutable('08:00');

        // When
        $appointment->setArrivalTime($time);
        $sqlTime = $appointment->getSqlArrivalTime();

        // Then
        $this->assertEquals('08:00', $sqlTime);
    }

    public function testSetAndGetDepartureTimeWithString(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $timeString = '10:00';
        $expectedTime = new \DateTimeImmutable($timeString);

        // When
        $appointment->setDepartureTime($timeString);
        $actualTime = $appointment->getDepartureTime();

        // Then
        $this->assertEquals($expectedTime, $actualTime);
    }

    public function testSetAndGetDepartureTimeWithDateTimeImmutable(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $time = new \DateTimeImmutable('10:00');

        // When
        $appointment->setDepartureTime($time);
        $actualTime = $appointment->getDepartureTime();

        // Then
        $this->assertEquals($time, $actualTime);
    }

    public function testGetSqlDepartureTime(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $time = new \DateTimeImmutable('10:00');

        // When
        $appointment->setDepartureTime($time);
        $sqlTime = $appointment->getSqlDepartureTime();

        // Then
        $this->assertEquals('10:00', $sqlTime);
    }

    public function testSetSupplier(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $supplier = $this->createMock(ThirdParty::class);

        // When
        $appointment->setSupplier($supplier);
        $actualSupplier = $appointment->getSupplier();

        // Then
        $this->assertSame($supplier, $actualSupplier);
    }

    public function testSetLoadingPlace(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $loadingPlace = $this->createMock(ThirdParty::class);

        // When
        $appointment->setLoadingPlace($loadingPlace);
        $actualLoadingPlace = $appointment->getLoadingPlace();

        // Then
        $this->assertSame($loadingPlace, $actualLoadingPlace);
    }

    public function testSetDeliveryPlace(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $deliveryPlace = $this->createMock(ThirdParty::class);

        // When
        $appointment->setDeliveryPlace($deliveryPlace);
        $actualDeliveryPlace = $appointment->getDeliveryPlace();

        // Then
        $this->assertSame($deliveryPlace, $actualDeliveryPlace);
    }

    public function testSetCustomer(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $customer = $this->createMock(ThirdParty::class);

        // When
        $appointment->setCustomer($customer);
        $actualCustomer = $appointment->getCustomer();

        // Then
        $this->assertSame($customer, $actualCustomer);
    }

    public function testSetCarrier(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $carrier = $this->createMock(ThirdParty::class);

        // When
        $appointment->setCarrier($carrier);
        $actualCarrier = $appointment->getCarrier();

        // Then
        $this->assertSame($carrier, $actualCarrier);
    }

    public function testSetTransportBroker(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $transportBroker = $this->createMock(ThirdParty::class);

        // When
        $appointment->setTransportBroker($transportBroker);
        $actualTransportBroker = $appointment->getTransportBroker();

        // Then
        $this->assertSame($transportBroker, $actualTransportBroker);
    }

    public function testSetReady(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->setReady(true);
        $actualReady = $appointment->isReady();

        // Then
        $this->assertTrue($actualReady);
    }

    public function testSetCharteringConfirmationSent(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->setCharteringConfirmationSent(true);
        $actualCharteringConfirmationSent = $appointment->isCharteringConfirmationSent();

        // Then
        $this->assertTrue($actualCharteringConfirmationSent);
    }

    public function testSetDeliveryNoteNumber(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $deliveryNoteNumber = 'DN12345';

        // When
        $appointment->setDeliveryNoteNumber($deliveryNoteNumber);
        $actualDeliveryNoteNumber = $appointment->getDeliveryNoteNumber();

        // Then
        $this->assertEquals($deliveryNoteNumber, $actualDeliveryNoteNumber);
    }

    public function testSetPublicComment(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $publicComment = 'This is a public comment.';

        // When
        $appointment->setPublicComment($publicComment);
        $actualComment = $appointment->getPublicComment();

        // Then
        $this->assertEquals($publicComment, $actualComment);
    }

    public function testSetPrivateComment(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $privateComment = 'This is a private comment.';

        // When
        $appointment->setPrivateComment($privateComment);
        $actualComment = $appointment->getPrivateComment();

        // Then
        $this->assertEquals($privateComment, $actualComment);
    }

    public function testSetAndGetDispatch(): void
    {
        // Given
        $bulkAppointment = new TimberAppointment();
        $dispatch = \array_fill(0, 3, new TimberDispatchItem());

        // When
        $bulkAppointment->setDispatch($dispatch);
        $actualIsDispatch = $bulkAppointment->getDispatch();

        // Then
        $this->assertSame($dispatch, $actualIsDispatch);
    }

    public function testToArray(): void
    {
        // Given
        $appointment =
            (new TimberAppointment())
            ->setId(1)
            ->setOnHold(true)
            ->setDate('2023-10-01')
            ->setArrivalTime('08:00')
            ->setDepartureTime('10:00')
            ->setSupplier((new ThirdParty())->setId(10))
            ->setLoadingPlace((new ThirdParty())->setId(20))
            ->setDeliveryPlace((new ThirdParty())->setId(30))
            ->setCustomer((new ThirdParty())->setId(40))
            ->setCarrier((new ThirdParty())->setId(50))
            ->setTransportBroker((new ThirdParty())->setId(60))
            ->setReady(true)
            ->setCharteringConfirmationSent(true)
            ->setDeliveryNoteNumber('DN12345')
            ->setPublicComment('This is a public comment.')
            ->setPrivateComment('This is a private comment.')
            ->setDispatch(
                \array_map(function (array $item) {
                    return (new TimberDispatchItem())
                        ->setStaff((new StevedoringStaff())->setId($item[0]))
                        ->setDate('2023-10-01')
                        ->setRemarks($item[1]);
                }, [[1, "A"], [2, "B"], [3, "C"]])
            );;

        $expectedArray = [
            'id' => 1,
            'attente' => true,
            'date_rdv' => '2023-10-01',
            'heure_arrivee' => '08:00',
            'heure_depart' => '10:00',
            'fournisseur' => 10,
            'chargement' => 20,
            'livraison' => 30,
            'client' => 40,
            'transporteur' => 50,
            'affreteur' => 60,
            'commande_prete' => true,
            'confirmation_affretement' => true,
            'numero_bl' => 'DN12345',
            'commentaire_public' => 'This is a public comment.',
            'commentaire_cache' => 'This is a private comment.',
            'dispatch' => [
                [
                    'appointmentId' => 1,
                    'staffId' => 1,
                    'date' => '2023-10-01',
                    'remarks' => 'A',
                ],
                [
                    'appointmentId' => 1,
                    'staffId' => 2,
                    'date' => '2023-10-01',
                    'remarks' => 'B',
                ],
                [
                    'appointmentId' => 1,
                    'staffId' => 3,
                    'date' => '2023-10-01',
                    'remarks' => 'C',
                ],
            ]
        ];

        // When
        $actualArray = $appointment->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
