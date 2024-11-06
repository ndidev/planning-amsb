<?php

// Path: api/tests/DTO/CharteringFilterDTOTest.php

namespace App\Tests\DTO;

use App\Core\HTTP\HTTPRequestQuery;
use App\DTO\Filter\CharteringFilterDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass(CharteringFilterDTO::class)]
final class CharteringFilterDTOTest extends TestCase
{
    public function testGetSqlStartDate(): void
    {
        // Given
        $date = '2023-01-01';
        $query = new HTTPRequestQuery(['date_debut' => $date]);
        $dto = new CharteringFilterDTO($query);

        // When
        $sqlStartDate = $dto->getSqlStartDate();

        // Then
        $this->assertSame($date, $sqlStartDate);
    }

    public function testGetSqlStartDateWithDefault(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
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
        $query = new HTTPRequestQuery(['date_debut' => '']);
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
        $query = new HTTPRequestQuery(['date_debut' => 'illegal']);
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
        $query = new HTTPRequestQuery(['date_fin' => $date]);
        $dto = new CharteringFilterDTO($query);

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($date, $sqlEndDate);
    }

    public function testGetSqlEndDateWithDefault(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
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
        $query = new HTTPRequestQuery(['date_fin' => '']);
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
        $query = new HTTPRequestQuery(['date_fin' => 'illegal']);
        $dto = new CharteringFilterDTO($query);
        $expected = (new \DateTime(CharteringFilterDTO::DEFAULT_END_DATE))->format('Y-m-d');

        // When
        $sqlEndDate = $dto->getSqlEndDate();

        // Then
        $this->assertSame($expected, $sqlEndDate);
    }

    public function testGetSqlChartererFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['affreteur' => '1,2']);
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertSame(" AND affreteur IN (1,2)", $result);
    }

    public function testGetEmptySqlChartererFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlChartererFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlOwnerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['armateur' => '1,2']);
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlOwnerFilter();

        // Then
        $this->assertSame(" AND armateur IN (1,2)", $result);
    }

    public function testGetEmptySqlOwnerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlOwnerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testGetSqlBrokerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery(['courtier' => '1,2']);
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlBrokerFilter();

        // Then
        $this->assertSame(" AND courtier IN (1,2)", $result);
    }

    public function testGetEmptySqlBrokerFilter(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->getSqlBrokerFilter();

        // Then
        $this->assertSame('', $result);
    }

    public function testIsArchive(): void
    {
        // Given
        $query = new HTTPRequestQuery(['archives' => '']);
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->isArchive();

        // Then
        $this->assertTrue($result);
    }

    public function testIsNotArchive(): void
    {
        // Given
        $query = new HTTPRequestQuery([]);
        $dto = new CharteringFilterDTO($query);

        // When
        $result = $dto->isArchive();

        // Then
        $this->assertFalse($result);
    }
}
