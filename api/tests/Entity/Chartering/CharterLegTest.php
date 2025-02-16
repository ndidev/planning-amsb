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

    public function testSetAndGetBlDate(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $blDate = new \DateTimeImmutable('2021-01-01');

        // When
        $charterLeg->blDate = $blDate;
        $actualBlDate = $charterLeg->blDate;
        $actualSqlBlDate = $charterLeg->sqlBlDate;

        // Then
        $this->assertEquals($blDate, $actualBlDate, 'The BL date is not the expected one.');
        $this->assertEquals('2021-01-01', $actualSqlBlDate, 'The SQL BL date is not the expected one.');
    }

    public function testToArray(): void
    {
        // Given
        $charterLeg = new CharterLeg();
        $charterLeg->id = 1;
        $charterLeg->charter = new Charter()->setId(10);
        $charterLeg->blDate = new \DateTimeImmutable('2021-01-01');
        $charterLeg->pol = new Port(['locode' => 'POL']);
        $charterLeg->pod = new Port(['locode' => 'POD']);
        $charterLeg->commodity = 'Commodity';
        $charterLeg->quantity = 'Quantity';
        $charterLeg->comments = 'Comments';

        $expectedArray = [
            'id' => 1,
            'charter' => 10,
            'bl_date' => '2021-01-01',
            'pol' => 'POL',
            'pod' => 'POD',
            'marchandise' => 'Commodity',
            'quantite' => 'Quantity',
            'commentaire' => 'Comments',
        ];

        // When
        $actualArray = $charterLeg->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
