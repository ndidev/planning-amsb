<?php

// Path: api/tests/DTO/ShippingFilterDTOTest.php

namespace App\Tests\DTO;

use App\DTO\ShippingFilterDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass(ShippingFilterDTO::class)]
final class ShippingFilterDTOTest extends TestCase
{
    public function testGetSqlStartDate(): void
    {
        // Given
        $query = ['date_debut' => '2023-01-01'];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlStartDate();

        // Then
        $this->assertSame('2023-01-01', $result);
    }

    public function testGetSqlStartDateWithDefault(): void
    {
        // Given
        $query = [];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlStartDate();

        // Then
        $this->assertSame(ShippingFilterDTO::DEFAULT_START_DATE, $result);
    }

    public function testGetSqlStartDateWithEmptyString(): void
    {
        // Given
        $query = ['date_debut' => ''];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlStartDate();

        // Then
        $this->assertSame(ShippingFilterDTO::DEFAULT_START_DATE, $result);
    }

    public function testGetSqlEndDate(): void
    {
        // Given
        $query = ['date_fin' => '2023-12-31'];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlEndDate();

        // Then
        $this->assertSame('2023-12-31', $result);
    }

    public function testGetSqlEndDateWithDefault(): void
    {
        // Given
        $query = [];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlEndDate();

        // Then
        $this->assertSame(ShippingFilterDTO::DEFAULT_END_DATE, $result);
    }

    public function testGetSqlEndDateWithEmptyString(): void
    {
        // Given
        $query = ['date_fin' => ''];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlEndDate();

        // Then
        $this->assertSame(ShippingFilterDTO::DEFAULT_END_DATE, $result);
    }

    public function testGetSqlShipFilter(): void
    {
        // Given
        $query = ['navire' => 'Ship1,Ship2'];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipFilter();

        // Then
        $this->assertSame(" AND cp.navire IN ('Ship1','Ship2')", $result);
    }

    public function testGetEmptySqlShipFilter(): void
    {
        // Given
        $query = [];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlShipOwnerFilter(): void
    {
        // Given
        $query = ['armateur' => '1,2'];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipOwnerFilter();

        // Then
        $this->assertSame(" AND cp.armateur IN (1,2)", $result);
    }

    public function testGetEmptySqlShipOwnerFilter(): void
    {
        // Given
        $query = [];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipOwnerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlCargoFilter(): void
    {
        // Given
        $query = ['marchandise' => 'Cargo1,Cargo2'];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCargoFilter();

        // Then
        $this->assertSame(" AND cem.marchandise IN ('Cargo1','Cargo2')", $result);
    }

    public function testGetEmptySqlCargoFilter(): void
    {
        // Given
        $query = [];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCargoFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlCustomerFilter(): void
    {
        // Given
        $query = ['client' => 'Customer1,Customer2'];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertSame(" AND cem.client IN ('Customer1','Customer2')", $result);
    }

    public function testGetEmptySqlCustomerFilter(): void
    {
        // Given
        $query = [];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlLastPortFilter(): void
    {
        // Given
        $query = ['last_port' => 'Port1,Port2'];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlLastPortFilter();

        // Then
        $this->assertSame(" AND cp.last_port IN ('Port1','Port2')", $result);
    }

    public function testGetEmptySqlLastPortFilter(): void
    {
        // Given
        $query = [];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlNextPortFilter(): void
    {
        // Given
        $query = ['next_port' => 'Port3,Port4'];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortFilter();

        // Then
        $this->assertSame(" AND cp.next_port IN ('Port3','Port4')", $result);
    }

    public function testGetEmptySqlNextPortFilter(): void
    {
        // Given
        $query = [];
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortFilter();

        // Then
        $this->assertSame('', $result);
    }
}
