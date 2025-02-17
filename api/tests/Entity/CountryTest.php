<?php

// Path: api/tests/Entity/CountryTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Country;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Country::class)]
final class CountryTest extends TestCase
{
    public function testConstructor(): void
    {
        // Given
        $iso = 'FR';
        $name = 'France';
        $data = [
            'iso' => $iso,
            'nom' => $name,
        ];

        // When
        $country = new Country($data);

        // Then
        $this->assertSame($iso, $country->iso);
        $this->assertSame($name, $country->name);
    }

    public function testToArray(): void
    {
        // Given
        $country = new Country();
        $name = 'France';
        $iso = 'FR';
        $country->name = $name;
        $country->iso = $iso;

        $expectedArray = [
            "iso" => $iso,
            "nom" => $name,
        ];

        // When
        $actualArray = $country->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
