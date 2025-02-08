<?php

// Path: api/tests/DTO/TimberRegistryEntryDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\TimberRegistryEntryDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberRegistryEntryDTO::class)]
class TimberRegistryEntryDTOTest extends TestCase
{
    public function testSetAndGetDate(): void
    {
        // Given
        $sqlDate = '2021-01-01';
        $expectedDate = '01/01/2021';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDate($sqlDate);
        $actualDate = $dto->getDate();

        $this->assertSame($expectedDate, $actualDate);
    }

    public function testSetAndGetInvalidDate(): void
    {
        // Given
        $invalidDate = 'invalid';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDate($invalidDate);
        $actualDate = $dto->getDate();

        // Then
        $this->assertSame($invalidDate, $actualDate);
    }

    public function testGetMonth(): void
    {
        // Given
        $sqlDate = '2021-01-01';
        $expectedMonth = 'janvier';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDate($sqlDate);
        $actualMonth = $dto->getMonth();

        // Then
        $this->assertSame($expectedMonth, $actualMonth);
    }

    public function testSetAndGetSupplierName(): void
    {
        // Given
        $supplierName = 'Supplier';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setSupplierName($supplierName);
        $actualSupplierName = $dto->getSupplierName();

        // Then
        $this->assertSame($supplierName, $actualSupplierName);
    }

    public function testSetAndGetNullSupplierName(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setSupplierName(null);
        $actualSupplierName = $dto->getSupplierName();

        // Then
        $this->assertSame('', $actualSupplierName);
    }

    public function testSetAndGetLoadingPlaceName(): void
    {
        // Given
        $loadingPlaceName = 'Loading Place';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setLoadingPlaceName($loadingPlaceName);
        $actualLoadingPlaceName = $dto->getLoadingPlaceName();

        // Then
        $this->assertSame($loadingPlaceName, $actualLoadingPlaceName);
    }

    public function testSetAndGetNullLoadingPlaceName(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setLoadingPlaceName(null);
        $actualLoadingPlaceName = $dto->getLoadingPlaceName();

        // Then
        $this->assertSame('', $actualLoadingPlaceName);
    }

    public function testSetAndGetLoadingPlaceCity(): void
    {
        // Given
        $loadingPlaceCity = 'Loading City';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setLoadingPlaceCity($loadingPlaceCity);
        $actualLoadingPlaceCity = $dto->getLoadingPlaceCity();

        // Then
        $this->assertSame($loadingPlaceCity, $actualLoadingPlaceCity);
    }

    public function testSetAndGetNullLoadingPlaceCity(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setLoadingPlaceCity(null);
        $actualLoadingPlaceCity = $dto->getLoadingPlaceCity();

        // Then
        $this->assertSame('', $actualLoadingPlaceCity);
    }

    public function testSetAndGetLoadingPlaceCountry(): void
    {
        // Given
        $loadingPlaceCountry = 'Loading Country';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setLoadingPlaceCountry($loadingPlaceCountry);
        $actualLoadingPlaceCountry = $dto->getLoadingPlaceCountry();

        // Then
        $this->assertSame($loadingPlaceCountry, $actualLoadingPlaceCountry);
    }

    public function testSetAndGetNullLoadingPlaceCountry(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setLoadingPlaceCountry(null);
        $actualLoadingPlaceCountry = $dto->getLoadingPlaceCountry();

        // Then
        $this->assertSame('', $actualLoadingPlaceCountry);
    }

    public function testSetAndGetDeliveryPlaceName(): void
    {
        // Given
        $deliveryPlaceName = 'Delivery Place';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlaceName($deliveryPlaceName);
        $actualDeliveryPlaceName = $dto->getDeliveryPlaceName();

        // Then
        $this->assertSame($deliveryPlaceName, $actualDeliveryPlaceName);
    }

    public function testSetAndGetNullDeliveryPlaceName(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlaceName(null);
        $actualDeliveryPlaceName = $dto->getDeliveryPlaceName();

        // Then
        $this->assertSame('', $actualDeliveryPlaceName);
    }

    public function testSetAndGetDeliveryPlacePostCode(): void
    {
        // Given
        $deliveryPlacePostCode = 'Delivery Post Code';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlacePostCode($deliveryPlacePostCode);
        $actualDeliveryPlacePostCode = $dto->getDeliveryPlacePostCode();

        // Then
        $this->assertSame($deliveryPlacePostCode, $actualDeliveryPlacePostCode);
    }

    public function testSetAndGetNullDeliveryPlacePostCode(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlacePostCode(null);
        $actualDeliveryPlacePostCode = $dto->getDeliveryPlacePostCode();

        // Then
        $this->assertSame('', $actualDeliveryPlacePostCode);
    }

    public function testSetAndGetDeliveryPlaceCity(): void
    {
        // Given
        $deliveryPlaceCity = 'Delivery City';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlaceCity($deliveryPlaceCity);
        $actualDeliveryPlaceCity = $dto->getDeliveryPlaceCity();

        // Then
        $this->assertSame($deliveryPlaceCity, $actualDeliveryPlaceCity);
    }

    public function testSetAndGetNullDeliveryPlaceCity(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlaceCity(null);
        $actualDeliveryPlaceCity = $dto->getDeliveryPlaceCity();

        // Then
        $this->assertSame('', $actualDeliveryPlaceCity);
    }

    public function testSetAndGetDeliveryPlaceCountry(): void
    {
        // Given
        $deliveryPlaceCountry = 'Loading Country';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlaceCountry($deliveryPlaceCountry);
        $actualDeliveryPlaceCountry = $dto->getDeliveryPlaceCountry();

        // Then
        $this->assertSame($deliveryPlaceCountry, $actualDeliveryPlaceCountry);
    }

    public function testSetAndGetNullDeliveryPlaceCountry(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlaceCountry(null);
        $actualDeliveryPlaceCountry = $dto->getDeliveryPlaceCountry();

        // Then
        $this->assertSame('', $actualDeliveryPlaceCountry);
    }

    public function testSetAndGetDeliveryNoteNumber(): void
    {
        // Given
        $deliveryNoteNumber = 'Delivery Note Number';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryNoteNumber($deliveryNoteNumber);
        $actualDeliveryNoteNumber = $dto->getDeliveryNoteNumber();

        // Then
        $this->assertSame($deliveryNoteNumber, $actualDeliveryNoteNumber);
    }

    public function testSetAndGetTransport(): void
    {
        // Given
        $transport = 'Transport';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setTransport($transport);
        $actualTransport = $dto->getTransport();

        // Then
        $this->assertSame($transport, $actualTransport);
    }

    public function testSetAndGetNullTransport(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setTransport(null);
        $actualTransport = $dto->getTransport();

        // Then
        $this->assertSame('', $actualTransport);
    }

    public function testGetFrenchLoadingPlace(): void
    {
        // Given
        $loadingPlaceName = 'Loading Place';
        $loadingPlaceCity = 'City';
        $loadingPlaceCountry = 'France';
        $expectedLoadingPlace = "Loading Place City";
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setLoadingPlaceName($loadingPlaceName);
        $dto->setLoadingPlaceCity($loadingPlaceCity);
        $dto->setLoadingPlaceCountry($loadingPlaceCountry);
        $actualLoadingPlace = $dto->getLoadingPlace();

        // Then
        $this->assertSame($expectedLoadingPlace, $actualLoadingPlace);
    }

    public function testGetNonFrenchLoadingPlace(): void
    {
        // Given
        $loadingPlaceName = 'Loading Place';
        $loadingPlaceCity = 'City';
        $loadingPlaceCountry = 'Country';
        $expectedLoadingPlace = "Loading Place City (Country)";
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setLoadingPlaceName($loadingPlaceName);
        $dto->setLoadingPlaceCity($loadingPlaceCity);
        $dto->setLoadingPlaceCountry($loadingPlaceCountry);
        $actualLoadingPlace = $dto->getLoadingPlace();

        // Then
        $this->assertSame($expectedLoadingPlace, $actualLoadingPlace);
    }

    public function testGetAmsbLoadingPlace(): void
    {
        // Given
        $loadingPlaceName = 'AMSB';
        $expectedLoadingPlace = 'AMSB';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setLoadingPlaceName($loadingPlaceName);
        $actualLoadingPlace = $dto->getLoadingPlace();

        // Then
        $this->assertSame($expectedLoadingPlace, $actualLoadingPlace);
    }

    public function testGetFrenchDeliveryPlace(): void
    {
        // Given
        $deliveryPlaceName = 'Delivery Place';
        $deliveryPlaceCity = 'City';
        $deliveryPlacePostCode = '12345';
        $deliveryPlaceCountry = 'France';
        $expectedDeliveryPlace = "Delivery Place 12 City";
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlaceName($deliveryPlaceName);
        $dto->setDeliveryPlaceCity($deliveryPlaceCity);
        $dto->setDeliveryPlacePostCode($deliveryPlacePostCode);
        $dto->setDeliveryPlaceCountry($deliveryPlaceCountry);
        $actualDeliveryPlace = $dto->getDeliveryPlace();

        // Then
        $this->assertSame($expectedDeliveryPlace, $actualDeliveryPlace);
    }

    public function testGetNonFrenchDeliveryPlace(): void
    {
        // Given
        $deliveryPlaceName = 'Delivery Place';
        $deliveryPlaceCity = 'City';
        $deliveryPlacePostCode = '12345';
        $deliveryPlaceCountry = 'Country';
        $expectedDeliveryPlace = "Delivery Place City (Country)";
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->setDeliveryPlaceName($deliveryPlaceName);
        $dto->setDeliveryPlaceCity($deliveryPlaceCity);
        $dto->setDeliveryPlacePostCode($deliveryPlacePostCode);
        $dto->setDeliveryPlaceCountry($deliveryPlaceCountry);
        $actualDeliveryPlace = $dto->getDeliveryPlace();

        // Then
        $this->assertSame($expectedDeliveryPlace, $actualDeliveryPlace);
    }

    public function testGetEmptyDeliveryPlace(): void
    {
        // Given
        $dto = new TimberRegistryEntryDTO();

        // When
        $actualDeliveryPlace = $dto->getDeliveryPlace();

        // Then
        $this->assertSame('Pas de lieu de livraison renseigné', $actualDeliveryPlace);
    }
}
