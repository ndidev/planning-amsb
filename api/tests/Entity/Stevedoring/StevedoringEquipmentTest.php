<?php

// Path: api/tests/Entity/Stevedoring/StevedoringEquipmentTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Stevedoring;

use App\Entity\Stevedoring\StevedoringEquipment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StevedoringEquipment::class)]
final class StevedoringEquipmentTest extends TestCase
{
    public function testToArray(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();
        $stevedoringEquipment->id = 1;
        $stevedoringEquipment->type = 'Grue';
        $stevedoringEquipment->brand = 'Liebherr';
        $stevedoringEquipment->model = 'LTM 11200-9.1';
        $stevedoringEquipment->internalNumber = '123456';
        $stevedoringEquipment->serialNumber = '987654';
        $stevedoringEquipment->comments = 'Commentaires';
        $stevedoringEquipment->isActive = true;

        $expectedArray = [
            'id' => 1,
            'type' => 'Grue',
            'brand' => 'Liebherr',
            'model' => 'LTM 11200-9.1',
            'internalNumber' => '123456',
            'serialNumber' => '987654',
            'displayName' => 'Liebherr LTM 11200-9.1 123456',
            'comments' => 'Commentaires',
            'isActive' => true,
        ];

        // When
        $actualArray = $stevedoringEquipment->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }

    public function testStringable(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();
        $stevedoringEquipment->brand = 'Liebherr';
        $stevedoringEquipment->model = 'LTM 11200-9.1';
        $stevedoringEquipment->internalNumber = '123456';

        $expectedString = 'Liebherr LTM 11200-9.1 123456';

        // When
        $actualString = (string) $stevedoringEquipment;

        // Then
        $this->assertSame($expectedString, $actualString);
    }
}
