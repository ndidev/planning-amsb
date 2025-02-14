<?php

// Path: api/tests/Entity/Shipping/ShippingCallTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Shipping;

use App\Core\Component\Collection;
use App\Entity\Port;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use App\Entity\Stevedoring\ShipReport;
use App\Entity\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShippingCall::class)]
#[UsesClass(ShippingCallCargo::class)]
#[UsesClass(ThirdParty::class)]
#[UsesClass(Port::class)]
final class ShippingCallTest extends TestCase
{
    #[TestDox('Default ship name is TBN')]
    public function testDefaultShipNameIsTbn(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 'TBN';

        // When
        $actual = $shippingCall->getShipName();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetShipName(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 'Ship name';

        // When
        $shippingCall->setShipName($expected);
        $actual = $shippingCall->getShipName();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetVoyage(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 'Voyage';

        // When
        $shippingCall->setVoyage($expected);
        $actual = $shippingCall->getVoyage();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetShipOperator(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new ThirdParty();

        // When
        $shippingCall->setShipOperator($expected);
        $actual = $shippingCall->getShipOperator();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetEtaDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->setEtaDate($expected);
        $actual = $shippingCall->getEtaDate();

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetEtaTime(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = '12:00';

        // When
        $shippingCall->setEtaTime($expected);
        $actual = $shippingCall->getEtaTime();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetNorDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->setNorDate($expected);
        $actual = $shippingCall->getNorDate();

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetNorTime(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = '12:00';

        // When
        $shippingCall->setNorTime($expected);
        $actual = $shippingCall->getNorTime();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetPobDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->setPobDate($expected);
        $actual = $shippingCall->getPobDate();

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetPobTime(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = '12:00';

        // When
        $shippingCall->setPobTime($expected);
        $actual = $shippingCall->getPobTime();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetEtbDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->setEtbDate($expected);
        $actual = $shippingCall->getEtbDate();

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetEtbTime(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = '12:00';

        // When
        $shippingCall->setEtbTime($expected);
        $actual = $shippingCall->getEtbTime();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetOpsDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->setOpsDate($expected);
        $actual = $shippingCall->getOpsDate();

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetOpsTime(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = '12:00';

        // When
        $shippingCall->setOpsTime($expected);
        $actual = $shippingCall->getOpsTime();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetEtcDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->setEtcDate($expected);
        $actual = $shippingCall->getEtcDate();

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetEtcTime(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = '12:00';

        // When
        $shippingCall->setEtcTime($expected);
        $actual = $shippingCall->getEtcTime();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetEtdDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->setEtdDate($expected);
        $actual = $shippingCall->getEtdDate();

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetEtdTime(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = '12:00';

        // When
        $shippingCall->setEtdTime($expected);
        $actual = $shippingCall->getEtdTime();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function setAndGetArrivalDraft(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 1.0;

        // When
        $shippingCall->setArrivalDraft($expected);
        $actual = $shippingCall->getArrivalDraft();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function setAndGetDepartureDraft(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 1.0;

        // When
        $shippingCall->setDepartureDraft($expected);
        $actual = $shippingCall->getDepartureDraft();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetLastPort(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new Port();

        // When
        $shippingCall->setLastPort($expected);
        $actual = $shippingCall->getLastPort();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetNextPort(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new Port();

        // When
        $shippingCall->setNextPort($expected);
        $actual = $shippingCall->getNextPort();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetCallPort(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 'Port';

        // When
        $shippingCall->setCallPort($expected);
        $actual = $shippingCall->getCallPort();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetQuay(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 'Quay';

        // When
        $shippingCall->setQuay($expected);
        $actual = $shippingCall->getQuay();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetComment(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 'Comment';

        // When
        $shippingCall->setComment($expected);
        $actual = $shippingCall->getComment();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetCargoes(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $cargoes = \array_fill(0, 3, new ShippingCallCargo());

        // When
        $shippingCall->setCargoes($cargoes);
        $actual = $shippingCall->getCargoes();

        // Then
        $this->assertInstanceOf(Collection::class, $actual);
        $this->assertCount(3, $actual);
        foreach ($cargoes as $cargo) {
            $this->assertContains($cargo, $actual);
        }
    }

    public function testToArray(): void
    {
        // Given
        $shippingCall = new ShippingCall();

        $shipReport = new ShipReport();
        $shipReport->id = 20;

        $shippingCall->id = 1;
        $shippingCall->shipReport = $shipReport;
        $shippingCall->shipName = 'Ship name';
        $shippingCall->voyageNumber = 'Voyage 1';
        $shippingCall->shipOperator = (new ThirdParty())->setId(10);
        $shippingCall->etaDate = new \DateTimeImmutable('2021-01-01');
        $shippingCall->etaTime = '12:00';
        $shippingCall->norDate = new \DateTimeImmutable('2021-01-02');
        $shippingCall->norTime = '13:00';
        $shippingCall->pobDate = new \DateTimeImmutable('2021-01-03');
        $shippingCall->pobTime = '14:00';
        $shippingCall->etbDate = new \DateTimeImmutable('2021-01-04');
        $shippingCall->etbTime = '15:00';
        $shippingCall->opsDate = new \DateTimeImmutable('2021-01-05');
        $shippingCall->opsTime = '16:00';
        $shippingCall->etcDate = new \DateTimeImmutable('2021-01-06');
        $shippingCall->etcTime = '17:00';
        $shippingCall->etdDate = new \DateTimeImmutable('2021-01-07');
        $shippingCall->etdTime = '18:00';
        $shippingCall->arrivalDraft = 1.0;
        $shippingCall->departureDraft = 2.0;
        $shippingCall->lastPort = new Port(['locode' => 'Last port']);
        $shippingCall->nextPort = new Port(['locode' => 'Next port']);
        $shippingCall->callPort = 'Port';
        $shippingCall->quay = 'Quay';
        $shippingCall->comment = 'Comment';
        $cargoes = \array_fill(0, 3, new ShippingCallCargo());
        $shippingCall->setCargoes($cargoes);

        $expected = [
            'id' => 1,
            'shipReportId' => 20,
            'navire' => 'Ship name',
            'voyage' => 'Voyage 1',
            'armateur' => 10,
            'eta_date' => '2021-01-01',
            'eta_heure' => '12:00',
            'nor_date' => '2021-01-02',
            'nor_heure' => '13:00',
            'pob_date' => '2021-01-03',
            'pob_heure' => '14:00',
            'etb_date' => '2021-01-04',
            'etb_heure' => '15:00',
            'ops_date' => '2021-01-05',
            'ops_heure' => '16:00',
            'etc_date' => '2021-01-06',
            'etc_heure' => '17:00',
            'etd_date' => '2021-01-07',
            'etd_heure' => '18:00',
            'te_arrivee' => 1.0,
            'te_depart' => 2.0,
            'last_port' => 'Last port',
            'next_port' => 'Next port',
            'call_port' => 'Port',
            'quai' => 'Quay',
            'commentaire' => 'Comment',
            'marchandises' => \array_map(fn(ShippingCallCargo $cargo) => $cargo->toArray(), $cargoes),
        ];

        // When
        $actual = $shippingCall->toArray();

        // Then
        $this->assertSame($expected, $actual);
    }
}
