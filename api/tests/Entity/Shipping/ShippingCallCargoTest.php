<?php

// Path: api/tests/Entity/Shipping/ShippingCallCargoTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Shipping;

use App\Core\Component\CargoOperation;
use App\Core\Exceptions\Client\BadRequestException;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShippingCallCargo::class)]
#[UsesClass(ShippingCall::class)]
#[UsesClass(CargoOperation::class)]
final class ShippingCallCargoTest extends TestCase
{
    public function testSetAndGetShippingCall(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $shippingCall = new ShippingCall();

        // When
        $shippingCallCargo->setShippingCall($shippingCall);
        $actualShippingCall = $shippingCallCargo->getShippingCall();

        // Then
        $this->assertSame($shippingCall, $actualShippingCall);
    }

    public function testSetAndGetCargoName(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $cargoName = 'Cargo Name';

        // When
        $shippingCallCargo->setCargoName($cargoName);
        $actualCargoName = $shippingCallCargo->getCargoName();

        // Then
        $this->assertSame($cargoName, $actualCargoName);
    }

    public function testSetAndGetCustomer(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $customer = 'Customer';

        // When
        $shippingCallCargo->setCustomer($customer);
        $actualCustomer = $shippingCallCargo->getCustomer();

        // Then
        $this->assertSame($customer, $actualCustomer);
    }

    public function testSetAndGetOperation(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $operation = CargoOperation::EXPORT;

        // When
        $shippingCallCargo->setOperation($operation);
        $actualOperation = $shippingCallCargo->getOperation();

        // Then
        $this->assertSame($operation, $actualOperation);
    }

    public function testExpectExceptionOnInvalidOperation(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $invalidOperation = 'invalid';

        // Then
        $this->expectException(BadRequestException::class);

        // When
        $shippingCallCargo->setOperation($invalidOperation);
    }

    public function testSetAndGetApproximateTrue(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();

        // When
        $shippingCallCargo->setApproximate(true);
        $actualApproximate = $shippingCallCargo->isApproximate();

        // Then
        $this->assertTrue($actualApproximate);
    }

    public function testSetAndGetApproximateFalse(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();

        // When
        $shippingCallCargo->setApproximate(false);
        $actualApproximate = $shippingCallCargo->isApproximate();

        // Then
        $this->assertFalse($actualApproximate);
    }

    public function testSetAndGetBlTonnage(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $blTonnage = 1000.0;

        // When
        $shippingCallCargo->setBlTonnage($blTonnage);
        $actualBlTonnage = $shippingCallCargo->getBlTonnage();

        // Then
        $this->assertSame($blTonnage, $actualBlTonnage);
    }

    public function testSetAndGetBlVolume(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $blVolume = 1000.0;

        // When
        $shippingCallCargo->setBlVolume($blVolume);
        $actualBlVolume = $shippingCallCargo->getBlVolume();

        // Then
        $this->assertSame($blVolume, $actualBlVolume);
    }

    public function testSetAndGetBlUnits(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $blUnits = 1000;

        // When
        $shippingCallCargo->setBlUnits($blUnits);
        $actualBlUnits = $shippingCallCargo->getBlUnits();

        // Then
        $this->assertSame($blUnits, $actualBlUnits);
    }

    public function testSetAndGetOutturnTonnage(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $outturnTonnage = 1000.0;

        // When
        $shippingCallCargo->setOutturnTonnage($outturnTonnage);
        $actualOutturnTonnage = $shippingCallCargo->getOutturnTonnage();

        // Then
        $this->assertSame($outturnTonnage, $actualOutturnTonnage);
    }

    public function testSetAndGetOutturnVolume(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $outturnVolume = 1000.0;

        // When
        $shippingCallCargo->setOutturnVolume($outturnVolume);
        $actualOutturnVolume = $shippingCallCargo->getOutturnVolume();

        // Then
        $this->assertSame($outturnVolume, $actualOutturnVolume);
    }

    public function testSetAndGetOutturnUnits(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $outturnUnits = 1000;

        // When
        $shippingCallCargo->setOutturnUnits($outturnUnits);
        $actualOutturnUnits = $shippingCallCargo->getOutturnUnits();

        // Then
        $this->assertSame($outturnUnits, $actualOutturnUnits);
    }

    public function testToArray(): void
    {
        // Given
        $shippingCallCargo =
            (new ShippingCallCargo())
            ->setId(1)
            ->setShippingCall((new ShippingCall())->setId(10))
            ->setCargoName('Cargo Name')
            ->setCustomer('Customer')
            ->setOperation(CargoOperation::EXPORT)
            ->setApproximate(true)
            ->setBlTonnage(1000.0)
            ->setBlVolume(1000.0)
            ->setBlUnits(1000)
            ->setOutturnTonnage(1000.0)
            ->setOutturnVolume(1000.0)
            ->setOutturnUnits(1000);

        $expectedArray = [
            'id' => 1,
            'escale_id' => 10,
            'marchandise' => 'Cargo Name',
            'client' => 'Customer',
            'operation' => CargoOperation::EXPORT,
            'environ' => true,
            'tonnage_bl' => 1000.0,
            'cubage_bl' => 1000.0,
            'nombre_bl' => 1000,
            'tonnage_outturn' => 1000.0,
            'cubage_outturn' => 1000.0,
            'nombre_outturn' => 1000,
        ];

        // When
        $actualArray = $shippingCallCargo->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
