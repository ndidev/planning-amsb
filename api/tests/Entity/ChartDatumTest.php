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
    public function testSetAndGetName(): void
    {
        // Given
        $chartDatum = new ChartDatum();
        $name = 'cote';

        // When
        $chartDatum->setName($name);
        $actualName = $chartDatum->getName();

        // Then
        $this->assertSame($name, $actualName);
    }

    public function testSetAndGetDisplayName(): void
    {
        // Given
        $chartDatum = new ChartDatum();
        $displayName = 'affichage';

        // When
        $chartDatum->setDisplayName($displayName);
        $actualDisplayName = $chartDatum->getDisplayName();

        // Then
        $this->assertSame($displayName, $actualDisplayName);
    }

    public function testSetAndGetValue(): void
    {
        // Given
        $chartDatum = new ChartDatum();
        $value = 1.0;

        // When
        $chartDatum->setValue($value);
        $actualValue = $chartDatum->getValue();

        // Then
        $this->assertSame($value, $actualValue);
    }

    public function testToArray(): void
    {
        // Given
        $chartDatum =
            (new ChartDatum())
            ->setName('cote')
            ->setDisplayName('affichage')
            ->setValue(1.0);

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
