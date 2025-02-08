<?php

// Path: api/tests/Entity/Chartering/CharterLegTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Chartering;

use App\Entity\Chartering\CharterLeg;
use App\Entity\Port;
use App\Entity\Chartering\Charter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CharterLeg::class)]
#[UsesClass(Charter::class)]
#[UsesClass(Port::class)]
final class CharterLegTest extends TestCase
{
    public function testSetAndGetCharter(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $charter = new Charter();

        // When
        $charterLeg->setCharter($charter);
        $actualCharter = $charterLeg->getCharter();

        // Then
        $this->assertSame($charter, $actualCharter);
    }

    public function testSetAndGetBlDate(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $blDate = new \DateTimeImmutable('2021-01-01');

        // When
        $charterLeg->setBlDate($blDate);
        $actualBlDate = $charterLeg->getBlDate();
        $actualSqlBlDate = $charterLeg->getSqlBlDate();

        // Then
        $this->assertEquals($blDate, $actualBlDate, 'The BL date is not the expected one.');
        $this->assertEquals('2021-01-01', $actualSqlBlDate, 'The SQL BL date is not the expected one.');
    }

    public function testSetAndGetPol(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $pol = new Port();

        // When
        $charterLeg->setPol($pol);
        $actualPol = $charterLeg->getPol();

        // Then
        $this->assertSame($pol, $actualPol);
    }

    public function testSetAndGetPod(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $pod = new Port();

        // When
        $charterLeg->setPod($pod);
        $actualPod = $charterLeg->getPod();

        // Then
        $this->assertSame($pod, $actualPod);
    }

    public function testSetAndGetCommodity(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $commodity = 'Commodity';

        // When
        $charterLeg->setCommodity($commodity);
        $actualCommodity = $charterLeg->getCommodity();

        // Then
        $this->assertSame($commodity, $actualCommodity);
    }

    public function testSetAndGetQuantity(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $quantity = 'Quantity';

        // When
        $charterLeg->setQuantity($quantity);
        $actualQuantity = $charterLeg->getQuantity();

        // Then
        $this->assertSame($quantity, $actualQuantity);
    }

    public function testSetAndGetComments(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $comments = 'Comments';

        // When
        $charterLeg->setComments($comments);
        $actualComments = $charterLeg->getComments();

        // Then
        $this->assertSame($comments, $actualComments);
    }

    public function testToArray(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $charter = (new Charter())->setId(10);
        $blDate = new \DateTimeImmutable('2021-01-01');
        $pol = (new Port())->setLocode('POL');
        $pod = (new Port())->setLocode('POD');
        $commodity = 'Commodity';
        $quantity = 'Quantity';
        $comments = 'Comments';

        $charterLeg
            ->setId(1)
            ->setCharter($charter)
            ->setBlDate($blDate)
            ->setPol($pol)
            ->setPod($pod)
            ->setCommodity($commodity)
            ->setQuantity($quantity)
            ->setComments($comments);

        $expectedArray = [
            'id' => 1,
            'charter' => 10,
            'bl_date' => '2021-01-01',
            'pol' => 'POL',
            'pod' => 'POD',
            'marchandise' => $commodity,
            'quantite' => $quantity,
            'commentaire' => $comments,
        ];

        // When
        $actualArray = $charterLeg->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
