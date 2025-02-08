<?php

// Path: api/tests/Entity/Chartering/CharterTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Chartering;

use App\Core\Component\CharterStatus;
use App\Core\Component\Collection;
use App\Entity\Chartering\Charter;
use App\Entity\Chartering\CharterLeg;
use App\Entity\Port;
use App\Entity\ThirdParty;
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
    public function testSetAndGetStatus(): void
    {
        // Given
        $charter = new Charter();
        $status = CharterStatus::PENDING;

        // When
        $charter->setStatus($status);
        $actualStatus = $charter->getStatus();

        // Then
        $this->assertSame($status, $actualStatus);
    }

    public function testSetAndGetLaycanStart(): void
    {
        // Given
        $charter = new Charter();
        $laycanStart = new \DateTimeImmutable('2021-01-01');

        // When
        $charter->setLaycanStart($laycanStart);
        $actualLaycanStart = $charter->getLaycanStart();
        $actualSqlLaycanStart = $charter->getSqlLaycanStart();

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
        $charter->setLaycanEnd($laycanEnd);
        $actualLaycanEnd = $charter->getLaycanEnd();
        $actualSqlLaycanEnd = $charter->getSqlLaycanEnd();

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
        $charter->setCpDate($cpDate);
        $actualCpDate = $charter->getCpDate();
        $actualSqlCpDate = $charter->getSqlCpDate();

        // Then
        $this->assertEquals($cpDate, $actualCpDate, 'The cp date is not the expected one.');
        $this->assertEquals('2021-01-01', $actualSqlCpDate, 'The SQL cp date is not the expected one.');
    }

    public function testSetANdGetVesselName(): void
    {
        // Given
        $charter = new Charter();
        $vesselName = 'Vessel Name';

        // When
        $charter->setVesselName($vesselName);
        $actualVesselName = $charter->getVesselName();

        // Then
        $this->assertSame($vesselName, $actualVesselName);
    }

    public function testSetAndGetCharterer(): void
    {
        // Given
        $charter = new Charter();
        $charterer = new ThirdParty();

        // When
        $charter->setCharterer($charterer);
        $actualCharterer = $charter->getCharterer();

        // Then
        $this->assertSame($charterer, $actualCharterer);
    }

    public function testSetAndGetShipOperator(): void
    {
        // Given
        $charter = new Charter();
        $shipOperator = new ThirdParty();

        // When
        $charter->setShipOperator($shipOperator);
        $actualShipOperator = $charter->getShipOperator();

        // Then
        $this->assertSame($shipOperator, $actualShipOperator);
    }

    public function testSetAndGetShipbroker(): void
    {
        // Given
        $charter = new Charter();
        $shipbroker = new ThirdParty();

        // When
        $charter->setShipbroker($shipbroker);
        $actualShipbroker = $charter->getShipbroker();

        // Then
        $this->assertSame($shipbroker, $actualShipbroker);
    }

    public function testSetAndGetFreightPayed(): void
    {
        // Given
        $charter = new Charter();
        $freightPayed = 1000.0;

        // When
        $charter->setFreightPayed($freightPayed);
        $actualFreightPayed = $charter->getFreightPayed();

        // Then
        $this->assertSame($freightPayed, $actualFreightPayed);
    }

    public function testSetAndGetFreightSold(): void
    {
        // Given
        $charter = new Charter();
        $freightSold = 1000.0;

        // When
        $charter->setFreightSold($freightSold);
        $actualFreightSold = $charter->getFreightSold();

        // Then
        $this->assertSame($freightSold, $actualFreightSold);
    }

    public function testSetAndGetDemurragePayed(): void
    {
        // Given
        $charter = new Charter();
        $demurragePayed = 1000.0;

        // When
        $charter->setDemurragePayed($demurragePayed);
        $actualDemurragePayed = $charter->getDemurragePayed();

        // Then
        $this->assertSame($demurragePayed, $actualDemurragePayed);
    }

    public function testSetAndGetDemurrageSold(): void
    {
        // Given
        $charter = new Charter();
        $demurrageSold = 1000.0;

        // When
        $charter->setDemurrageSold($demurrageSold);
        $actualDemurrageSold = $charter->getDemurrageSold();

        // Then
        $this->assertSame($demurrageSold, $actualDemurrageSold);
    }

    public function testSetAndGetComments(): void
    {
        // Given
        $charter = new Charter();
        $comments = 'Comments';

        // When
        $charter->setComments($comments);
        $actualComments = $charter->getComments();

        // Then
        $this->assertSame($comments, $actualComments);
    }

    public function testSetAndGetArchiveTrue(): void
    {
        // Given
        $charter = new Charter();
        $archiveState = true;

        // When
        $charter->setArchive($archiveState);
        $actualArchiveState = $charter->isArchive();

        // Then
        $this->assertTrue($actualArchiveState);
    }

    public function testSetAndGetArchiveFalse(): void
    {
        // Given
        $charter = new Charter();
        $archiveState = false;

        // When
        $charter->setArchive($archiveState);
        $actualArchiveState = $charter->isArchive();

        // Then
        $this->assertFalse($actualArchiveState);
    }

    public function testSetAndGetLegs(): void
    {
        // Given
        $charter = new Charter();
        $legs = array_fill(0, 3, new CharterLeg());

        // When
        $charter->setLegs($legs);
        $actualLegs = $charter->getLegs();

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
        $actualLegs = $charter->getLegs();

        // Then
        $this->assertContains($leg, $actualLegs);
    }

    public function testToArray(): void
    {
        // Given
        $charter = new Charter();
        $charter->setId(1);
        $charter->setStatus(CharterStatus::PENDING);
        $charter->setLaycanStart(new \DateTimeImmutable('2021-01-01'));
        $charter->setLaycanEnd(new \DateTimeImmutable('2021-01-01'));
        $charter->setCpDate(new \DateTimeImmutable('2021-01-01'));
        $charter->setVesselName('Vessel Name');
        $charterer = (new ThirdParty())->setId(10);
        $charter->setCharterer($charterer);
        $shipOperator = (new ThirdParty())->setId(20);
        $charter->setShipOperator($shipOperator);
        $shipbroker = (new ThirdParty())->setId(30);
        $charter->setShipbroker($shipbroker);
        $charter->setFreightPayed(1000);
        $charter->setFreightSold(1000);
        $charter->setDemurragePayed(1000);
        $charter->setDemurrageSold(1000);
        $charter->setComments('Comments');
        $charter->setArchive(true);
        $legs = array_fill(0, 3, new CharterLeg());
        $charter->setLegs($legs);

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
