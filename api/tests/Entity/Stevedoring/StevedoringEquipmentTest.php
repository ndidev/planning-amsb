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
    public function testSetAndGetType(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();
        $type = 'Grue';

        // When
        $stevedoringEquipment->setType($type);
        $actualType = $stevedoringEquipment->getType();

        // Then
        $this->assertSame($type, $actualType);
    }

    public function testSetAndGetBrand(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();
        $brand = 'Liebherr';

        // When
        $stevedoringEquipment->setBrand($brand);
        $actualBrand = $stevedoringEquipment->getBrand();

        // Then
        $this->assertSame($brand, $actualBrand);
    }

    public function testSetAndGetModel(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();
        $model = 'LTM 11200-9.1';

        // When
        $stevedoringEquipment->setModel($model);
        $actualModel = $stevedoringEquipment->getModel();

        // Then
        $this->assertSame($model, $actualModel);
    }

    public function testSetAndGetInternalNumber(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();
        $internalNumber = '123456';

        // When
        $stevedoringEquipment->setInternalNumber($internalNumber);
        $actualInternalNumber = $stevedoringEquipment->getInternalNumber();

        // Then
        $this->assertSame($internalNumber, $actualInternalNumber);
    }

    public function testSetAndGetSerialNumber(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();
        $serialNumber = '987654';

        // When
        $stevedoringEquipment->setSerialNumber($serialNumber);
        $actualSerialNumber = $stevedoringEquipment->getSerialNumber();

        // Then
        $this->assertSame($serialNumber, $actualSerialNumber);
    }

    public function testSetAndGetComments(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();
        $comments = 'Commentaires';

        // When
        $stevedoringEquipment->setComments($comments);
        $actualComments = $stevedoringEquipment->getComments();

        // Then
        $this->assertSame($comments, $actualComments);
    }

    public function testSetAndIsActive(): void
    {
        // Given
        $stevedoringEquipment = new StevedoringEquipment();
        $isActive = false;

        // When
        $stevedoringEquipment->setActive($isActive);
        $actualIsActive = $stevedoringEquipment->isActive();

        // Then
        $this->assertSame($isActive, $actualIsActive);
    }

    public function testToArray(): void
    {
        // Given
        $stevedoringEquipment =
            (new StevedoringEquipment())
            ->setId(1)
            ->setType('Grue')
            ->setBrand('Liebherr')
            ->setModel('LTM 11200-9.1')
            ->setInternalNumber('123456')
            ->setSerialNumber('987654')
            ->setComments('Commentaires')
            ->setActive(true);

        $expectedArray = [
            'id' => 1,
            'type' => 'Grue',
            'brand' => 'Liebherr',
            'model' => 'LTM 11200-9.1',
            'internalNumber' => '123456',
            'serialNumber' => '987654',
            'comments' => 'Commentaires',
            'isActive' => true,
        ];

        // When
        $actualArray = $stevedoringEquipment->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
