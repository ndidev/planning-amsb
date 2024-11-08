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
        $_SERVER['REQUEST_URI'] = "/path?date_debut={$date}";
        $query = HTTPRequestQuery::buildFromRequest();
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
        $query = HTTPRequestQuery::buildFromRequest();
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
        $_SERVER['REQUEST_URI'] = "/path?date_debut=";
        $query = HTTPRequestQuery::buildFromRequest();
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
        $_SERVER['REQUEST_URI'] = "/path?date_debut=illegal";
        $query = HTTPRequestQuery::buildFromRequest();
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
        $_SERVER['REQUEST_URI'] = "/path?date_fin={$date}";
        $query = HTTPRequestQuery::buildFromRequest();
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
        $query = HTTPRequestQuery::buildFromRequest();
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
        $_SERVER['REQUEST_URI'] = "/path?date_fin=";
        $query = HTTPRequestQuery::buildFromRequest();
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
        $_SERVER['REQUEST_URI'] = "/path?date_fin=illegal";
        $query = HTTPRequestQuery::buildFromRequest();
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
        $_SERVER['REQUEST_URI'] = "/path?navire=Ship1,Ship2";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipFilter();

        // Then
        $this->assertSame(" AND cp.navire IN ('Ship1','Ship2')", $result);
    }

    public function testGetEmptySqlShipFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlShipOwnerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?armateur=1,2";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipOwnerFilter();

        // Then
        $this->assertSame(" AND cp.armateur IN (1,2)", $result);
    }

    public function testGetEmptySqlShipOwnerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlShipOwnerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlCargoFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?marchandise=Cargo1,Cargo2";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCargoFilter();

        // Then
        $this->assertSame(" AND cem.marchandise IN ('Cargo1','Cargo2')", $result);
    }

    public function testGetEmptySqlCargoFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCargoFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlCustomerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?client=Customer1,Customer2";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertSame(" AND cem.client IN ('Customer1','Customer2')", $result);
    }

    public function testGetEmptySqlCustomerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlCustomerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlLastPortFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?last_port=Port1,Port2";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlLastPortFilter();

        // Then
        $this->assertSame(" AND cp.last_port IN ('Port1','Port2')", $result);
    }

    public function testGetEmptySqlLastPortFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlNextPortFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?next_port=Port3,Port4";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortFilter();

        // Then
        $this->assertSame(" AND cp.next_port IN ('Port3','Port4')", $result);
    }

    public function testGetEmptySqlNextPortFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = HTTPRequestQuery::buildFromRequest();
        $dto = new ShippingFilterDTO($query);

        // When
        $result = $dto->getSqlNextPortFilter();

        // Then
        $this->assertSame('', $result);
    }
}
