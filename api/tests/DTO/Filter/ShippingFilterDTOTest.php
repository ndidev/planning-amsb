<?php

// Path: api/tests/DTO/Filter/ShippingFilterDTOTest.php

declare(strict_types=1);

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
        $_SERVER['REQUEST_URI'] = "/path?startDate={$date}";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertSame($date, $sqlStartDate);
    }

    public function testGetSqlStartDateWithDefault(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
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
        $_SERVER['REQUEST_URI'] = "/path?startDate=";
        $query = new HTTPRequestQuery();
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
        $_SERVER['REQUEST_URI'] = "/path?startDate=illegal";
        $query = new HTTPRequestQuery();
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
        $_SERVER['REQUEST_URI'] = "/path?endDate={$date}";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($date, $sqlEndDate);
    }

    public function testGetSqlEndDateWithDefault(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
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
        $_SERVER['REQUEST_URI'] = "/path?endDate=";
        $query = new HTTPRequestQuery();
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
        $_SERVER['REQUEST_URI'] = "/path?endDate=illegal";
        $query = new HTTPRequestQuery();
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
        $_SERVER['REQUEST_URI'] = "/path?ships=Ship1,Ship2";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipsFilter();

        // Then
        $this->assertSame(" AND cp.navire IN ('Ship1','Ship2')", $result);
    }

    public function testGetEmptySqlShipFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipsFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlShipOwnerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?shipOwners=1,2";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipOwnersFilter();

        // Then
        $this->assertSame(" AND cp.armateur IN (1,2)", $result);
    }

    public function testGetEmptySqlShipOwnerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipOwnersFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlStrictCargoFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?cargoes=Cargo1,Cargo2&strictCargoes=true";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCargoesFilter();

        // Then
        $this->assertSame(" AND cem.marchandise IN ('Cargo1','Cargo2')", $result);
    }

    public function testGetSqlNonStrictCargoFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?cargoes=Cargo1,Cargo2";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCargoesFilter();

        // Then
        $this->assertSame(" AND cem.marchandise REGEXP 'Cargo1|Cargo2'", $result);
    }

    public function testGetEmptySqlCargoFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCargoesFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlCustomerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?customers=Customer1,Customer2";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCustomersFilter();

        // Then
        $this->assertSame(" AND cem.client IN ('Customer1','Customer2')", $result);
    }

    public function testGetEmptySqlCustomerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCustomersFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlLastPortFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?lastPorts=Port1,Port2";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlLastPortsFilter();

        // Then
        $this->assertSame(" AND cp.last_port IN ('Port1','Port2')", $result);
    }

    public function testGetEmptySqlLastPortFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlLastPortsFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlNextPortFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?nextPorts=Port3,Port4";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortsFilter();

        // Then
        $this->assertSame(" AND cp.next_port IN ('Port3','Port4')", $result);
    }

    public function testGetEmptySqlNextPortFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortsFilter();

        // Then
        $this->assertSame('', $result);
    }
}
