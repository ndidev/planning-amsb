<?php

// Path: api/tests/DTO/ShippingFilterDTOTest.php

namespace App\Tests\DTO;

use App\Core\HTTP\HTTPRequestQuery;
use App\DTO\Filter\ShippingFilterDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass(ShippingFilterDTO::class)]
final class ShippingFilterDTOTest extends TestCase
{
    public function testGetSqlStartDate(): void
    {
        // Given
        $date = '2023-01-01';
        $query = new HTTPRequestQuery(['date_debut' => $date]);
        $dto = new ShippingFilterDTO($query);

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertSame($date, $sqlStartDate);
    }

    public function testGetSqlStartDateWithDefault(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new ShippingFilterDTO($query);
        $expected = (new \DateTime(ShippingFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertSame($expected, $sqlStartDate);
    }

    public function testGetSqlStartDateWithEmptyString(): void
    {
        // Given
        $query = new HTTPRequestQuery(['date_debut' => '']);
        $dto = new ShippingFilterDTO($query);
        $expected = (new \DateTime(ShippingFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertSame($expected, $sqlStartDate);
    }

    public function testGetSqlStartDateWithIllegalString(): void
    {
        // Given
        $query = new HTTPRequestQuery(['date_debut' => 'illegal']);
        $dto = new ShippingFilterDTO($query);
        $expected = (new \DateTime(ShippingFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertSame($expected, $sqlStartDate);
    }

    public function testGetSqlEndDate(): void
    {
        // Given
        $date = '2023-12-31';
        $query = new HTTPRequestQuery(['date_fin' => $date]);
        $dto = new ShippingFilterDTO($query);

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($date, $sqlEndDate);
    }

    public function testGetSqlEndDateWithDefault(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new ShippingFilterDTO($query);
        $expected = (new \DateTime(ShippingFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($expected, $sqlEndDate);
    }

    public function testGetSqlEndDateWithEmptyString(): void
    {
        // Given
        $query = new HTTPRequestQuery(['date_fin' => '']);
        $dto = new ShippingFilterDTO($query);
        $expected = (new \DateTime(ShippingFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($expected, $sqlEndDate);
    }

    public function testGetSqlEndDateWithIllegalString(): void
    {
        // Given
        $query = new HTTPRequestQuery(['date_fin' => 'illegal']);
        $dto = new ShippingFilterDTO($query);
        $expected = (new \DateTime(ShippingFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($expected, $sqlEndDate);
    }

    public function testGetSqlShipFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['navire' => 'Ship1,Ship2']);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipFilter();

        // Then
        $this->assertSame(" AND cp.navire IN ('Ship1','Ship2')", $result);
    }

    public function testGetEmptySqlShipFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlShipOwnerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['armateur' => '1,2']);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipOwnerFilter();

        // Then
        $this->assertSame(" AND cp.armateur IN (1,2)", $result);
    }

    public function testGetEmptySqlShipOwnerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipOwnerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlCargoFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['marchandise' => 'Cargo1,Cargo2']);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCargoFilter();

        // Then
        $this->assertSame(" AND cem.marchandise IN ('Cargo1','Cargo2')", $result);
    }

    public function testGetEmptySqlCargoFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCargoFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlCustomerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['client' => 'Customer1,Customer2']);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertSame(" AND cem.client IN ('Customer1','Customer2')", $result);
    }

    public function testGetEmptySqlCustomerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlLastPortFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['last_port' => 'Port1,Port2']);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlLastPortFilter();

        // Then
        $this->assertSame(" AND cp.last_port IN ('Port1','Port2')", $result);
    }

    public function testGetEmptySqlLastPortFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlNextPortFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['next_port' => 'Port3,Port4']);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortFilter();

        // Then
        $this->assertSame(" AND cp.next_port IN ('Port3','Port4')", $result);
    }

    public function testGetEmptySqlNextPortFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortFilter();

        // Then
        $this->assertSame('', $result);
    }
}
