<?php

// Path: api/tests/Entity/PortTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Port;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Port::class)]
final class PortTest extends TestCase
{
    public function testSetAndGetLocode(): void
    {
        // Given
        $port = new Port();
        $locode = 'FRLEH';

        // When
        $port->setLocode($locode);
        $actual = $port->getLocode();

        // Then
        $this->assertSame($locode, $actual);
    }

    public function testSetAndGetName(): void
    {
        // Given
        $port = new Port();
        $name = 'Le Havre';

        // When
        $port->setName($name);
        $actual = $port->getName();

        // Then
        $this->assertSame($name, $actual);
    }

    public function testSetAndGetDisplayName(): void
    {
        // Given
        $port = new Port();
        $displayName = 'Le Havre';

        // When
        $port->setDisplayName($displayName);
        $actual = $port->getDisplayName();

        // Then
        $this->assertSame($displayName, $actual);
    }

    public function testToArray(): void
    {
        // Given
        $port =
            (new Port())
            ->setLocode('FRLEH')
            ->setName('Le Havre')
            ->setDisplayName('Le Havre, France');

        $expectedArray = [
            "locode" => 'FRLEH',
            "nom" => 'Le Havre',
            "nom_affichage" => 'Le Havre, France',
        ];

        // When
        $actual = $port->toArray();

        // Then
        $this->assertSame($expectedArray, $actual);
    }
}
