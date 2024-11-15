<?php

// Path: api/tests/DTO/Filter/CharteringFilterDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\Core\HTTP\HTTPRequestQuery;
use App\DTO\Filter\CharteringFilterDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;


#[CoversClass(CharteringFilterDTO::class)]
#[UsesClass(HTTPRequestQuery::class)]
final class CharteringFilterDTOTest extends TestCase
{
    public function testGetSqlStartDate(): void
    {
        // Given
        $date = '2023-01-01';
        $_SERVER['REQUEST_URI'] = "/path?date_debut={$date}";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

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
        $dto = new CharteringFilterDTO($query);
        $expected = (new \DateTime(CharteringFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertSame($expected, $sqlStartDate);
    }

    public function testGetSqlStartDateWithEmptyString(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?date_debut=";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);
        $expected = (new \DateTime(CharteringFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertSame($expected, $sqlStartDate);
    }

    public function testGetSqlStartDateWithIllegalString(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?date_debut=illegal";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);
        $expected = (new \DateTime(CharteringFilterDTO::DEFAULT_START_DATE))->format('Y-m-d');

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
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

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
        $dto = new CharteringFilterDTO($query);
        $expected = (new \DateTime(CharteringFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($expected, $sqlEndDate);
    }

    public function testGetSqlEndDateWithEmptyString(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?date_fin=";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);
        $expected = (new \DateTime(CharteringFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($expected, $sqlEndDate);
    }

    public function testGetSqlEndDateWithIllegalString(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?date_fin=illegal";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);
        $expected = (new \DateTime(CharteringFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($expected, $sqlEndDate);
    }

    public function testGetSqlStatusFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?statut=1,2";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlStatusFilter();

        // Then
        $this->assertSame(" AND statut IN (1,2)", $result);
    }

    public function testGetEmptySqlStatusFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlStatusFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlChartererFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?affreteur=1,2";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertSame(" AND affreteur IN (1,2)", $result);
    }

    public function testGetEmptySqlChartererFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlOwnerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?armateur=1,2";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlOwnerFilter();

        // Then
        $this->assertSame(" AND armateur IN (1,2)", $result);
    }

    public function testGetEmptySqlOwnerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlOwnerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlBrokerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?courtier=1,2";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlBrokerFilter();

        // Then
        $this->assertSame(" AND courtier IN (1,2)", $result);
    }

    public function testGetEmptySqlBrokerFilter(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlBrokerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testIsArchive(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?archives";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->isArchive();

        // Then
        $this->assertTrue($result);
    }

    public function testIsNotArchive(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->isArchive();

        // Then
        $this->assertFalse($result);
    }
}
