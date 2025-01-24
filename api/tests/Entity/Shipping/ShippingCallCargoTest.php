<?php

// Path: api/tests/Entity/Shipping/ShippingCallCargoTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Shipping;

use App\Core\Component\CargoOperation;
use App\Core\Exceptions\Client\BadRequestException;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use App\Entity\Stevedoring\ShipReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShippingCallCargo::class)]
#[UsesClass(ShippingCall::class)]
#[UsesClass(CargoOperation::class)]
final class ShippingCallCargoTest extends TestCase
{

    public function testSetAndGetOperation(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $operation = CargoOperation::EXPORT;

        // When
        $shippingCallCargo->operation = $operation;
        $actualOperation = $shippingCallCargo->operation;

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
        // @phpstan-ignore assign.propertyType
        $shippingCallCargo->operation = $invalidOperation;
    }

    public function testToArray(): void
    {
        // Given
        $shippingCallCargo = new ShippingCallCargo();
        $shippingCall = new ShippingCall();
        $shippingCall->id = 10;
        $shipReport = new ShipReport();
        $shipReport->id = 20;

        $shippingCallCargo->id = 1;
        $shippingCallCargo->shippingCall = $shippingCall;
        $shippingCallCargo->shipReport = $shipReport;
        $shippingCallCargo->cargoName = 'Cargo Name';
        $shippingCallCargo->customer = 'Customer';
        $shippingCallCargo->operation = CargoOperation::EXPORT;
        $shippingCallCargo->isApproximate = true;
        $shippingCallCargo->blTonnage = 1000.0;
        $shippingCallCargo->blVolume = 2000.0;
        $shippingCallCargo->blUnits = 3000;
        $shippingCallCargo->outturnTonnage = 999.0;
        $shippingCallCargo->outturnVolume = 2002.0;
        $shippingCallCargo->outturnUnits = 997;

        $expectedArray = [
            'id' => 1,
            'escale_id' => 10,
            'shipReportId' => 20,
            'cargoName' => 'Cargo Name',
            'customer' => 'Customer',
            'operation' => CargoOperation::EXPORT,
            'isApproximate' => true,
            'blTonnage' => 1000.0,
            'blVolume' => 2000.0,
            'blUnits' => 3000,
            'outturnTonnage' => 999.0,
            'outturnVolume' => 2002.0,
            'outturnUnits' => 997,
            'tonnageDifference' => -1.0,
            'volumeDifference' => 2.0,
            'unitsDifference' => -2003,
        ];

        // When
        $actualArray = $shippingCallCargo->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
