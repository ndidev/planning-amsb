<?php

// Path: api/tests/Entity/Config/TimberQuickAppointmentAddTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Config;

use App\Core\Component\Module;
use App\Entity\Config\TimberQuickAppointmentAdd;
use App\Entity\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberQuickAppointmentAdd::class)]
#[UsesClass(ThirdParty::class)]
#[UsesClass(Module::class)]
final class TimberQuickAppointmentAddTest extends TestCase
{
    public function testGetModule(): void
    {
        // Given
        $timberQuickAppointmentAdd = new TimberQuickAppointmentAdd();

        // When
        $module = $timberQuickAppointmentAdd->getModule();

        // Then
        $this->assertSame(Module::TIMBER, $module);
    }

    public function testSetAndGetSupplier(): void
    {
        // Given
        $timberQuickAppointmentAdd = new TimberQuickAppointmentAdd();
        $supplier = new ThirdParty();

        // When
        $timberQuickAppointmentAdd->setSupplier($supplier);
        $result = $timberQuickAppointmentAdd->getSupplier();

        // Then
        $this->assertSame($supplier, $result);
    }

    public function testSetAndGetCarrier(): void
    {
        // Given
        $timberQuickAppointmentAdd = new TimberQuickAppointmentAdd();
        $carrier = new ThirdParty();

        // When
        $timberQuickAppointmentAdd->setCarrier($carrier);
        $result = $timberQuickAppointmentAdd->getCarrier();

        // Then
        $this->assertSame($carrier, $result);
    }

    public function testSetAndGetCharterer(): void
    {
        // Given
        $timberQuickAppointmentAdd = new TimberQuickAppointmentAdd();
        $charterer = new ThirdParty();

        // When
        $timberQuickAppointmentAdd->setCharterer($charterer);
        $result = $timberQuickAppointmentAdd->getCharterer();

        // Then
        $this->assertSame($charterer, $result);
    }

    public function testSetAndGetLoading(): void
    {
        // Given
        $timberQuickAppointmentAdd = new TimberQuickAppointmentAdd();
        $loading = new ThirdParty();

        // When
        $timberQuickAppointmentAdd->setLoading($loading);
        $result = $timberQuickAppointmentAdd->getLoading();

        // Then
        $this->assertSame($loading, $result);
    }

    public function testSetAndGetCustomer(): void
    {
        // Given
        $timberQuickAppointmentAdd = new TimberQuickAppointmentAdd();
        $customer = new ThirdParty();

        // When
        $timberQuickAppointmentAdd->setCustomer($customer);
        $result = $timberQuickAppointmentAdd->getCustomer();

        // Then
        $this->assertSame($customer, $result);
    }

    public function testSetAndGetDelivery(): void
    {
        // Given
        $timberQuickAppointmentAdd = new TimberQuickAppointmentAdd();
        $delivery = new ThirdParty();

        // When
        $timberQuickAppointmentAdd->setDelivery($delivery);
        $result = $timberQuickAppointmentAdd->getDelivery();

        // Then
        $this->assertSame($delivery, $result);
    }

    public function testToArray(): void
    {
        // Given
        $timberQuickAppointmentAdd = new TimberQuickAppointmentAdd();
        $supplier = (new ThirdParty())->setId(10);
        $carrier = (new ThirdParty())->setId(20);
        $charterer = (new ThirdParty())->setId(30);
        $loading = (new ThirdParty())->setId(40);
        $customer = (new ThirdParty())->setId(50);
        $delivery = (new ThirdParty())->setId(60);

        $timberQuickAppointmentAdd
            ->setId(1)
            ->setSupplier($supplier)
            ->setCarrier($carrier)
            ->setCharterer($charterer)
            ->setLoading($loading)
            ->setCustomer($customer)
            ->setDelivery($delivery);

        $expectedArray = [
            'id' => 1,
            'module' => Module::TIMBER,
            'fournisseur' => 10,
            'transporteur' => 20,
            'affreteur' => 30,
            'chargement' => 40,
            'client' => 50,
            'livraison' => 60,
        ];

        // When
        $result = $timberQuickAppointmentAdd->toArray();

        // Then
        $this->assertSame($expectedArray, $result);
    }
}
