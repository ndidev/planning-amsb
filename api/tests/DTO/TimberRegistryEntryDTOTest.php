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
        $dto->date = $sqlDate;
        $actualDate = $dto->date;

        $this->assertSame($expectedDate, $actualDate);
    }

    public function testSetAndGetInvalidDate(): void
    {
        // Given
        $invalidDate = 'invalid';
        $dto = new TimberRegistryEntryDTO();

        // When
        $dto->date = $invalidDate;
        $actualDate = $dto->date;

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
        $dto->date = $sqlDate;
        $actualMonth = $dto->month;

        // Then
        $this->assertSame($expectedMonth, $actualMonth);
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
        $dto->loadingPlaceName = $loadingPlaceName;
        $dto->loadingPlaceCity = $loadingPlaceCity;
        $dto->loadingPlaceCountry = $loadingPlaceCountry;
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
        $dto->loadingPlaceName = $loadingPlaceName;
        $dto->loadingPlaceCity = $loadingPlaceCity;
        $dto->loadingPlaceCountry = $loadingPlaceCountry;
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
        $dto->loadingPlaceName = $loadingPlaceName;
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
        $dto->deliveryPlaceName = $deliveryPlaceName;
        $dto->deliveryPlaceCity = $deliveryPlaceCity;
        $dto->deliveryPlacePostCode = $deliveryPlacePostCode;
        $dto->deliveryPlaceCountry = $deliveryPlaceCountry;
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
        $dto->deliveryPlaceName = $deliveryPlaceName;
        $dto->deliveryPlaceCity = $deliveryPlaceCity;
        $dto->deliveryPlacePostCode = $deliveryPlacePostCode;
        $dto->deliveryPlaceCountry = $deliveryPlaceCountry;
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
