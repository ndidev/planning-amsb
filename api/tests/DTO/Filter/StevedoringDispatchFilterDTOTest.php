<?php

// Path: api/tests/DTO/Filter/StevedoringDispatchFilterDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\Core\HTTP\HTTPRequestQuery;
use App\DTO\Filter\StevedoringDispatchFilterDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;


#[CoversClass(StevedoringDispatchFilterDTO::class)]
#[UsesClass(HTTPRequestQuery::class)]
final class StevedoringDispatchFilterDTOTest extends TestCase
{
    public function testGetSqlStartDate(): void
    {
        // Given
        $date = '2023-01-01';
        $_SERVER['REQUEST_URI'] = "/path?startDate={$date}";
        $query = new HTTPRequestQuery();
        $dto = new StevedoringDispatchFilterDTO($query);

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
        $dto = new StevedoringDispatchFilterDTO($query);
        $expected = (new \DateTime(StevedoringDispatchFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

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
        $dto = new StevedoringDispatchFilterDTO($query);
        $expected = (new \DateTime(StevedoringDispatchFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

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
        $dto = new StevedoringDispatchFilterDTO($query);
        $expected = (new \DateTime(StevedoringDispatchFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

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
        $dto = new StevedoringDispatchFilterDTO($query);

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
        $dto = new StevedoringDispatchFilterDTO($query);
        $expected = (new \DateTime(StevedoringDispatchFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

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
        $dto = new StevedoringDispatchFilterDTO($query);
        $expected = (new \DateTime(StevedoringDispatchFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

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
        $dto = new StevedoringDispatchFilterDTO($query);
        $expected = (new \DateTime(StevedoringDispatchFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($expected, $sqlEndDate);
    }

    public function testGetSqlStaffFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?staff=1,2";
        $query = new HTTPRequestQuery();
        $dto = new StevedoringDispatchFilterDTO($query);

        // When
        $result = $dto->getSqlStaffFilter();

        // Then
        $this->assertSame(" AND staff_id IN (1,2)", $result);
    }

    public function testGetEmptySqlStaffFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new StevedoringDispatchFilterDTO($query);

        // When
        $result = $dto->getSqlStaffFilter();

        // Then
        $this->assertSame('', $result);
    }
}
