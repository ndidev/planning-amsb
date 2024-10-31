<?php

// Path: api/tests/DTO/TimberFilterDTOTest.php

namespace App\Tests\DTO;

use App\DTO\TimberFilterDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberFilterDTO::class)]
final class TimberFilterDTOTest extends TestCase
{
    public function testGetSqlStartDate(): void
    {
        // Given
        $query = ['date_debut' => '2023-01-01'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlStartDate();

        // Then
        $this->assertEquals('2023-01-01', $result);
    }

    public function testGetSqlStartDateWithDefault(): void
    {
        // Given
        $query = [];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlStartDate();

        // Then
        $this->assertEquals(TimberFilterDTO::DEFAULT_START_DATE, $result);
    }

    public function testGetSqlStartDateWithEmptyString(): void
    {
        // Given
        $query = ['date_debut' => ''];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlStartDate();

        // Then
        $this->assertEquals(TimberFilterDTO::DEFAULT_START_DATE, $result);
    }

    public function testGetSqlEndDate(): void
    {
        // Given
        $query = ['date_fin' => '2023-12-31'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlEndDate();

        // Then
        $this->assertEquals('2023-12-31', $result);
    }

    public function testGetSqlEndDateWithDefault(): void
    {
        // Given
        $query = [];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlEndDate();

        // Then
        $this->assertEquals(TimberFilterDTO::DEFAULT_END_DATE, $result);
    }

    public function testGetSqlEndDateWithEmptyString(): void
    {
        // Given
        $query = ['date_fin' => ''];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlEndDate();

        // Then
        $this->assertEquals(TimberFilterDTO::DEFAULT_END_DATE, $result);
    }

    public function testGetSqlSupplierFilter(): void
    {
        // Given
        $query = ['fournisseur' => '1,2'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlSupplierFilter();

        // Then
        $this->assertEquals(' AND fournisseur IN (1,2)', $result);
    }

    public function testGetSqlSupplierFilterWithTrailingComma(): void
    {
        // Given
        $query = ['fournisseur' => '1,2,'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlSupplierFilter();

        // Then
        $this->assertEquals(' AND fournisseur IN (1,2)', $result);
    }

    public function testGetEmptySqlSupplierFilter(): void
    {
        // Given
        $query = [];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlSupplierFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlCustomerFilter(): void
    {
        // Given
        $query = ['client' => '1,2,'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertEquals(' AND client IN (1,2)', $result);
    }

    public function testGetSqlCustomerFilterWithTrailingComma(): void
    {
        // Given
        $query = ['client' => '1,2,'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertEquals(' AND client IN (1,2)', $result);
    }

    public function testGetEmptySqlCustomerFilter(): void
    {
        // Given
        $query = [];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlLoadingPlaceFilter(): void
    {
        // Given
        $query = ['chargement' => '1,2'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlLoadingPlaceFilter();

        // Then
        $this->assertEquals(' AND chargement IN (1,2)', $result);
    }

    public function testGetSqlLoadingPlaceFilterWithTrailingComma(): void
    {
        // Given
        $query = ['chargement' => '1,2,'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlLoadingPlaceFilter();

        // Then
        $this->assertEquals(' AND chargement IN (1,2)', $result);
    }

    public function testGetEmptySqlLoadingPlaceFilter(): void
    {
        // Given
        $query = [];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlLoadingPlaceFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlDeliveryPlaceFilter(): void
    {
        // Given
        $query = ['livraison' => '3,4'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlDeliveryPlaceFilter();

        // Then
        $this->assertEquals(' AND livraison IN (3,4)', $result);
    }

    public function testGetSqlDeliveryPlaceFilterWithTrailingComma(): void
    {
        // Given
        $query = ['livraison' => '3,4,'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlDeliveryPlaceFilter();

        // Then
        $this->assertEquals(' AND livraison IN (3,4)', $result);
    }

    public function testGetEmptySqlDeliveryPlaceFilter(): void
    {
        // Given
        $query = [];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlDeliveryPlaceFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlTransportFilter(): void
    {
        // Given
        $query = ['transporteur' => '1,2'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlTransportFilter();

        // Then
        $this->assertEquals(' AND transporteur IN (1,2)', $result);
    }

    public function testGetSqlTransportFilterWithTrailingComma(): void
    {
        // Given
        $query = ['transporteur' => '1,2,'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlTransportFilter();

        // Then
        $this->assertEquals(' AND transporteur IN (1,2)', $result);
    }

    public function testGetEmptySqlTransportFilter(): void
    {
        // Given
        $query = [];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlTransportFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlChartererFilter(): void
    {
        // Given
        $query = ['affreteur' => '1,2'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertEquals(' AND affreteur IN (1,2)', $result);
    }

    public function testGetSqlChartererFilterWithTrailingComma(): void
    {
        // Given
        $query = ['affreteur' => '1,2,'];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertEquals(' AND affreteur IN (1,2)', $result);
    }

    public function testGetEmptySqlChartererFilter(): void
    {
        // Given
        $query = [];
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertEquals('', $result);
    }
}
