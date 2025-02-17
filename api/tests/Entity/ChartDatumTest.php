<?php

// Path: api/tests/Entity/ChartDatumTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ChartDatum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChartDatum::class)]
final class ChartDatumTest extends TestCase
{
    public function testToArray(): void
    {
        // Given
        $chartDatum = new ChartDatum();
        $chartDatum->name = 'cote';
        $chartDatum->displayName = 'affichage';
        $chartDatum->value = 1.0;

        $expectedArray = [
            'cote' => 'cote',
            'affichage' => 'affichage',
            'valeur' => 1.0,
        ];

        // When
        $actualArray = $chartDatum->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
