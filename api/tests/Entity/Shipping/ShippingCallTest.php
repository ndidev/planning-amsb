<?php

// Path: api/tests/Entity/Shipping/ShippingCallTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Shipping;

use App\Core\Component\Collection;
use App\Entity\Port;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use App\Entity\Stevedoring\ShipReport;
use App\Entity\ThirdParty\ThirdParty;
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
        $actual = $shippingCall->shipName;

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetShipName(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 'Ship name';

        // When
        $shippingCall->shipName = $expected;
        $actual = $shippingCall->shipName;

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetEmptyShipName(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = 'TBN';

        // When
        $shippingCall->shipName = '';
        $actual = $shippingCall->shipName;

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetEtaDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->etaDate = $expected;
        $actual = $shippingCall->etaDate;

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetNorDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->norDate = $expected;
        $actual = $shippingCall->norDate;

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetPobDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->pobDate = $expected;
        $actual = $shippingCall->pobDate;

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetEtbDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->etbDate = $expected;
        $actual = $shippingCall->etbDate;

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetOpsDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->opsDate = $expected;
        $actual = $shippingCall->opsDate;

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetEtcDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->etcDate = $expected;
        $actual = $shippingCall->etcDate;

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetEtdDate(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $expected = new \DateTimeImmutable('2021-01-01');

        // When
        $shippingCall->etdDate = $expected;
        $actual = $shippingCall->etdDate;

        // Then
        $this->assertEquals($expected, $actual);
    }

    public function testSetAndGetCargoes(): void
    {
        // Given
        $shippingCall = new ShippingCall();
        $cargoes = \array_fill(0, 3, new ShippingCallCargo());

        // When
        $shippingCall->cargoes = $cargoes;
        $actual = $shippingCall->cargoes;

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
        $shippingCall->id = 1;
        $shippingCall->shipReport = new ShipReport()->setId(20);
        $shippingCall->shipName = 'Ship name';
        $shippingCall->voyageNumber = 'Voyage 1';
        $shippingCall->shipOperator = new ThirdParty()->setId(10);
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
        $shippingCall->berth = 'Quay';
        $shippingCall->comment = 'Comment';
        $cargoes = \array_fill(0, 3, new ShippingCallCargo());
        $shippingCall->cargoes = $cargoes;

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
            'marchandises' => \array_map(fn($cargo) => $cargo->toArray(), $cargoes),
        ];

        // When
        $actual = $shippingCall->toArray();

        // Then
        $this->assertSame($expected, $actual);
    }
}
