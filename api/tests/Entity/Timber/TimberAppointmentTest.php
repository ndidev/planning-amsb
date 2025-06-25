<?php

// Path: api/tests/Entity/Timber/TimberAppointmentTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Timber;

use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\ThirdParty\ThirdParty;
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
    public function testSetAndGetOnHoldWithBool(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->isOnHold = true;
        $actualOnHold = $appointment->isOnHold;

        // Then
        $this->assertTrue($actualOnHold);
    }

    public function testSetAndGetOnHoldWithInt(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->isOnHold = 1;
        $actualOnHold = $appointment->isOnHold;

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
        $appointment->date = $dateString;
        $actualDate = $appointment->date;

        // Then
        $this->assertEquals($expectedDate, $actualDate);
    }

    public function testSetAndGetDateWithDateTimeImmutable(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $date = new \DateTimeImmutable('2023-10-01');

        // When
        $appointment->date = $date;
        $actualDate = $appointment->date;

        // Then
        $this->assertEquals($date, $actualDate);
    }

    public function testGetSqlDate(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $date = new \DateTimeImmutable('2023-10-01');

        // When
        $appointment->date = $date;
        $sqlDate = $appointment->sqlDate;

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
        $appointment->arrivalTime = $timeString;
        $actualTime = $appointment->arrivalTime;

        // Then
        $this->assertEquals($expectedTime, $actualTime);
    }

    public function testSetAndGetArrivalTimeWithDateTimeImmutable(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $time = new \DateTimeImmutable('08:00');

        // When
        $appointment->arrivalTime = $time;
        $actualTime = $appointment->arrivalTime;

        // Then
        $this->assertEquals($time, $actualTime);
    }

    public function testGetSqlArrivalTime(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $time = new \DateTimeImmutable('08:00');

        // When
        $appointment->arrivalTime = $time;
        $sqlTime = $appointment->sqlArrivalTime;

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
        $appointment->departureTime = $timeString;
        $actualTime = $appointment->departureTime;

        // Then
        $this->assertEquals($expectedTime, $actualTime);
    }

    public function testSetAndGetDepartureTimeWithDateTimeImmutable(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $time = new \DateTimeImmutable('10:00');

        // When
        $appointment->departureTime = $time;
        $actualTime = $appointment->departureTime;

        // Then
        $this->assertEquals($time, $actualTime);
    }

    public function testGetSqlDepartureTime(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $time = new \DateTimeImmutable('10:00');

        // When
        $appointment->departureTime = $time;
        $sqlTime = $appointment->sqlDepartureTime;

        // Then
        $this->assertEquals('10:00', $sqlTime);
    }

    public function testSetReadyWithBool(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->isReady = true;
        $actualReady = $appointment->isReady;

        // Then
        $this->assertTrue($actualReady);
    }

    public function testSetReadyWithInt(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->isReady = 1;
        $actualReady = $appointment->isReady;

        // Then
        $this->assertTrue($actualReady);
    }

    public function testSetCharteringConfirmationSentWithBool(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->isCharteringConfirmationSent = true;
        $actualCharteringConfirmationSent = $appointment->isCharteringConfirmationSent;

        // Then
        $this->assertTrue($actualCharteringConfirmationSent);
    }

    public function testSetCharteringConfirmationSentWithInt(): void
    {
        // Given
        $appointment = new TimberAppointment();

        // When
        $appointment->isCharteringConfirmationSent = 1;
        $actualCharteringConfirmationSent = $appointment->isCharteringConfirmationSent;

        // Then
        $this->assertTrue($actualCharteringConfirmationSent);
    }

    public function testSetAndGetDispatch(): void
    {
        // Given
        $bulkAppointment = new TimberAppointment();
        $dispatch = \array_fill(0, 3, new TimberDispatchItem());

        // When
        $bulkAppointment->dispatch = $dispatch;
        $actualIsDispatch = $bulkAppointment->dispatch;

        // Then
        $this->assertSame($dispatch, $actualIsDispatch);
    }

    public function testToArray(): void
    {
        // Given
        $appointment = new TimberAppointment();
        $appointment->id = 1;
        $appointment->isOnHold = true;
        $appointment->date = '2023-10-01';
        $appointment->arrivalTime = '08:00';
        $appointment->departureTime = '10:00';
        $appointment->supplier = new ThirdParty()->setId(10);
        $appointment->loadingPlace = new ThirdParty()->setId(20);
        $appointment->deliveryPlace = new ThirdParty()->setId(30);
        $appointment->customer = new ThirdParty()->setId(40);
        $appointment->carrier = new ThirdParty()->setId(50);
        $appointment->transportBroker = new ThirdParty()->setId(60);
        $appointment->isReady = true;
        $appointment->isCharteringConfirmationSent = true;
        $appointment->deliveryNoteNumber = 'DN12345';
        $appointment->publicComment = 'This is a public comment.';
        $appointment->privateComment = 'This is a private comment.';
        $appointment->dispatch =
            \array_map(function (array $item) {
                $dispatchItem = new TimberDispatchItem();
                $dispatchItem->staff = new StevedoringStaff(['id' => $item[0]]);
                $dispatchItem->date = '2023-10-01';
                $dispatchItem->remarks = $item[1];

                return $dispatchItem;
            }, [[1, "A"], [2, "B"], [3, "C"]]);

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
