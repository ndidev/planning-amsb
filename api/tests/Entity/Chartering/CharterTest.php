<?php

// Path: api/tests/Entity/Chartering/CharterTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Chartering;

use App\Core\Component\CharterStatus;
use App\Core\Component\Collection;
use App\Entity\Chartering\Charter;
use App\Entity\Chartering\CharterLeg;
use App\Entity\Port;
use App\Entity\ThirdParty\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Charter::class)]
#[UsesClass(ThirdParty::class)]
#[UsesClass(CharterLeg::class)]
#[UsesClass(Port::class)]
#[UsesClass(CharterStatus::class)]
final class CharterTest extends TestCase
{
    public function testSetAndGetValidStatus(): void
    {
        // Given
        $charter = new Charter();
        $status = CharterStatus::PENDING;

        // When
        $charter->status = $status;
        $actualStatus = $charter->status;

        // Then
        $this->assertSame($status, $actualStatus);
    }

    public function testSetAndGetInvalidStatus(): void
    {
        // Given
        $charter = new Charter();
        $status = 999;
        $expected = CharterStatus::PENDING;

        // When
        $charter->status = $status; // @phpstan-ignore assign.propertyType
        $actualStatus = $charter->status;

        // Then
        $this->assertSame($expected, $actualStatus);
    }

    public function testSetAndGetLaycanStart(): void
    {
        // Given
        $charter = new Charter();
        $laycanStart = new \DateTimeImmutable('2021-01-01');

        // When
        $charter->laycanStart = $laycanStart;
        $actualLaycanStart = $charter->laycanStart;
        $actualSqlLaycanStart = $charter->sqlLaycanStart;

        // Then
        $this->assertEquals($laycanStart, $actualLaycanStart, 'The laycan start is not the expected one.');
        $this->assertEquals('2021-01-01', $actualSqlLaycanStart, 'The SQL laycan start is not the expected one.');
    }

    public function testSetAndGetLaycanEnd(): void
    {
        // Given
        $charter = new Charter();
        $laycanEnd = new \DateTimeImmutable('2021-01-01');

        // When
        $charter->laycanEnd = $laycanEnd;
        $actualLaycanEnd = $charter->laycanEnd;
        $actualSqlLaycanEnd = $charter->sqlLaycanEnd;

        // Then
        $this->assertEquals($laycanEnd, $actualLaycanEnd, 'The laycan end is not the expected one.');
        $this->assertEquals('2021-01-01', $actualSqlLaycanEnd, 'The SQL laycan end is not the expected one.');
    }

    public function testSetAndGetCpDate(): void
    {
        // Given
        $charter = new Charter();
        $cpDate = new \DateTimeImmutable('2021-01-01');

        // When
        $charter->cpDate = $cpDate;
        $actualCpDate = $charter->cpDate;
        $actualSqlCpDate = $charter->sqlCpDate;

        // Then
        $this->assertEquals($cpDate, $actualCpDate, 'The cp date is not the expected one.');
        $this->assertEquals('2021-01-01', $actualSqlCpDate, 'The SQL cp date is not the expected one.');
    }

    public function testSetAndGetVesselName(): void
    {
        // Given
        $charter = new Charter();
        $vesselName = 'Vessel Name';

        // When
        $charter->vesselName = $vesselName;
        $actualVesselName = $charter->vesselName;

        // Then
        $this->assertSame($vesselName, $actualVesselName);
    }

    public function testSetAndGetEmptyVesselName(): void
    {
        // Given
        $charter = new Charter();
        $vesselName = '';
        $expected = 'TBN';

        // When
        $charter->vesselName = $vesselName;
        $actualVesselName = $charter->vesselName;

        // Then
        $this->assertSame($expected, $actualVesselName);
    }

    public function testSetAndGetLegs(): void
    {
        // Given
        $charter = new Charter();
        $legs = \array_fill(0, 3, new CharterLeg());

        // When
        $charter->legs = $legs;
        $actualLegs = $charter->legs;

        // Then
        $this->assertInstanceOf(Collection::class, $actualLegs);
        $this->assertCount(3, $actualLegs);
        foreach ($legs as $leg) {
            $this->assertContains($leg, $actualLegs);
        }
    }

    public function testAddLeg(): void
    {
        // Given
        $charter = new Charter();
        $leg = new CharterLeg();

        // When
        $charter->addLeg($leg);
        $actualLegs = $charter->legs;

        // Then
        $this->assertContains($leg, $actualLegs);
    }

    public function testToArray(): void
    {
        // Given
        $charter = new Charter();
        $charter->id = 1;
        $charter->status = CharterStatus::PENDING;
        $charter->laycanStart = new \DateTimeImmutable('2021-01-01');
        $charter->laycanEnd = new \DateTimeImmutable('2021-01-01');
        $charter->cpDate = new \DateTimeImmutable('2021-01-01');
        $charter->vesselName = 'Vessel Name';
        $charter->charterer = new ThirdParty()->setId(10);
        $charter->shipOperator = new ThirdParty()->setId(20);
        $charter->shipbroker = new ThirdParty()->setId(30);
        $charter->freightPayed = 1000;
        $charter->freightSold = 1000;
        $charter->demurragePayed = 1000;
        $charter->demurrageSold = 1000;
        $charter->comments = 'Comments';
        $charter->isArchive = true;
        $legs = \array_fill(0, 3, new CharterLeg());
        $charter->legs = $legs;

        $expectedArray = [
            'id' => 1,
            'statut' => CharterStatus::PENDING,
            'lc_debut' => '2021-01-01',
            'lc_fin' => '2021-01-01',
            'cp_date' => '2021-01-01',
            'navire' => 'Vessel Name',
            'affreteur' => 10,
            'armateur' => 20,
            'courtier' => 30,
            'fret_achat' => 1000.0,
            'fret_vente' => 1000.0,
            'surestaries_achat' => 1000.0,
            'surestaries_vente' => 1000.0,
            'commentaire' => 'Comments',
            'archive' => true,
            'legs' => \array_map(fn(CharterLeg $leg) => $leg->toArray(), $legs),
        ];

        // When
        $actualArray = $charter->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
