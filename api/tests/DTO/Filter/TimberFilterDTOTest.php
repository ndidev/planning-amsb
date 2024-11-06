<?php

// Path: api/tests/DTO/TimberFilterDTOTest.php

namespace App\Tests\DTO;

use App\Core\HTTP\HTTPRequestQuery;
use App\DTO\Filter\TimberFilterDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberFilterDTO::class)]
final class TimberFilterDTOTest extends TestCase
{
    public function testGetSqlStartDate(): void
    {
        // Given
        $date = '2023-01-01';
        $query = new HTTPRequestQuery(['date_debut' => $date]);
        $dto = new TimberFilterDTO($query);

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertEquals($date, $sqlStartDate);
    }

    public function testGetSqlStartDateWithDefault(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new TimberFilterDTO($query);
        $expected = (new \DateTime(TimberFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertEquals($expected, $sqlStartDate);
    }

    public function testGetSqlStartDateWithEmptyString(): void
    {
        // Given
        $query = new HTTPRequestQuery(['date_debut' => '']);
        $dto = new TimberFilterDTO($query);
        $expected = (new \DateTime(TimberFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertEquals($expected, $sqlStartDate);
    }

    public function testGetSqlStartDateWithIllegalString(): void
    {
        // Given
        $query = new HTTPRequestQuery(['date_debut' => 'illegal']);
        $dto = new TimberFilterDTO($query);
        $expected = (new \DateTime(TimberFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertEquals($expected, $sqlStartDate);
    }

    public function testGetSqlEndDate(): void
    {
        // Given
        $date = '2023-12-31';
        $query = new HTTPRequestQuery(['date_fin' => $date]);
        $dto = new TimberFilterDTO($query);

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertEquals($date, $sqlEndDate);
    }

    public function testGetSqlEndDateWithDefault(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new TimberFilterDTO($query);
        $expected = (new \DateTime(TimberFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertEquals($expected, $sqlEndDate);
    }

    public function testGetSqlEndDateWithEmptyString(): void
    {
        // Given
        $query = new HTTPRequestQuery(['date_fin' => '']);
        $dto = new TimberFilterDTO($query);
        $expected = (new \DateTime(TimberFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertEquals($expected, $sqlEndDate);
    }

    public function testGetSqlEndDateWithIllegalString(): void
    {
        // Given
        $query = new HTTPRequestQuery(['date_fin' => 'illegal']);
        $dto = new TimberFilterDTO($query);
        $expected = (new \DateTime(TimberFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertEquals($expected, $sqlEndDate);
    }

    public function testGetSqlSupplierFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['fournisseur' => '1,2']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlSupplierFilter();

        // Then
        $this->assertEquals(' AND fournisseur IN (1,2)', $result);
    }

    public function testGetSqlSupplierFilterWithTrailingComma(): void
    {
        // Given
        $query = new HTTPRequestQuery(['fournisseur' => '1,2,']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlSupplierFilter();

        // Then
        $this->assertEquals(' AND fournisseur IN (1,2)', $result);
    }

    public function testGetEmptySqlSupplierFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlSupplierFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlCustomerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['client' => '1,2,']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertEquals(' AND client IN (1,2)', $result);
    }

    public function testGetSqlCustomerFilterWithTrailingComma(): void
    {
        // Given
        $query = new HTTPRequestQuery(['client' => '1,2,']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertEquals(' AND client IN (1,2)', $result);
    }

    public function testGetEmptySqlCustomerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlLoadingPlaceFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['chargement' => '1,2']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlLoadingPlaceFilter();

        // Then
        $this->assertEquals(' AND chargement IN (1,2)', $result);
    }

    public function testGetSqlLoadingPlaceFilterWithTrailingComma(): void
    {
        // Given
        $query = new HTTPRequestQuery(['chargement' => '1,2,']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlLoadingPlaceFilter();

        // Then
        $this->assertEquals(' AND chargement IN (1,2)', $result);
    }

    public function testGetEmptySqlLoadingPlaceFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlLoadingPlaceFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlDeliveryPlaceFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['livraison' => '3,4']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlDeliveryPlaceFilter();

        // Then
        $this->assertEquals(' AND livraison IN (3,4)', $result);
    }

    public function testGetSqlDeliveryPlaceFilterWithTrailingComma(): void
    {
        // Given
        $query = new HTTPRequestQuery(['livraison' => '3,4,']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlDeliveryPlaceFilter();

        // Then
        $this->assertEquals(' AND livraison IN (3,4)', $result);
    }

    public function testGetEmptySqlDeliveryPlaceFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlDeliveryPlaceFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlTransportFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['transporteur' => '1,2']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlTransportFilter();

        // Then
        $this->assertEquals(' AND transporteur IN (1,2)', $result);
    }

    public function testGetSqlTransportFilterWithTrailingComma(): void
    {
        // Given
        $query = new HTTPRequestQuery(['transporteur' => '1,2,']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlTransportFilter();

        // Then
        $this->assertEquals(' AND transporteur IN (1,2)', $result);
    }

    public function testGetEmptySqlTransportFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlTransportFilter();

        // Then
        $this->assertEquals('', $result);
    }

    public function testGetSqlChartererFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['affreteur' => '1,2']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertEquals(' AND affreteur IN (1,2)', $result);
    }

    public function testGetSqlChartererFilterWithTrailingComma(): void
    {
        // Given
        $query = new HTTPRequestQuery(['affreteur' => '1,2,']);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertEquals(' AND affreteur IN (1,2)', $result);
    }

    public function testGetEmptySqlChartererFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new TimberFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertEquals('', $result);
    }
}
