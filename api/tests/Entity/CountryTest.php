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
    public function testSetAndGetISO(): void
    {
        // Given
        $country = new Country();
        $iso = 'FR';

        // When
        $country->setISO($iso);
        $actualISO = $country->getISO();

        // Then
        $this->assertSame($iso, $actualISO);
    }

    public function testSetAndGetName(): void
    {
        // Given
        $country = new Country();
        $name = 'France';

        // When
        $country->setName($name);
        $actualName = $country->getName();

        // Then
        $this->assertSame($name, $actualName);
    }

    public function testToArray(): void
    {
        // Given
        $country = new Country();
        $name = 'France';
        $iso = 'FR';
        $country->setName($name);
        $country->setISO($iso);

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
