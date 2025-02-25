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
    public function testConstructor(): void
    {
        // Given
        $data = [
            'locode' => 'FRLEH',
            'nom' => 'Le Havre',
            'nom_affichage' => 'Le Havre, France',
        ];

        // When
        $port = new Port($data);

        // Then
        $this->assertSame('FRLEH', $port->locode);
        $this->assertSame('Le Havre', $port->name);
        $this->assertSame('Le Havre, France', $port->displayName);
    }

    public function testToArray(): void
    {
        // Given
        $port = new Port();
        $port->locode = 'FRLEH';
        $port->name = 'Le Havre';
        $port->displayName = 'Le Havre, France';

        $expectedArray = [
            'locode' => 'FRLEH',
            'nom' => 'Le Havre',
            'nom_affichage' => 'Le Havre, France',
        ];

        // When
        $actual = $port->toArray();

        // Then
        $this->assertSame($expectedArray, $actual);
    }
}
