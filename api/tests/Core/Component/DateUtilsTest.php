<?php

// Path: api/tests/Core/Component/DateUtilsTest.php

declare(strict_types=1);

namespace App\Tests\Core\Component;

use PHPUnit\Framework\TestCase;
use App\Core\Component\DateUtils;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class DateUtilsTest extends TestCase
{
    public function testOutputsValidDateString(): void
    {
        // Given
        $date = "2022-02-28T20:01:36+0100";
        $expected = "lundi 28 février 2022";

        // When
        $actual = DateUtils::format(DateUtils::DATE_FULL, $date);

        // Then
        $this->assertEquals($expected, $actual);
    }

    #[DataProvider("generateFrenchPublicHolidaysFor2022")]
    public function testPublicHolidaysAreCorrect(
        string $name,
        string $dateAsString
    ): void {
        // Given
        $date = new \DateTimeImmutable($dateAsString);

        // When
        $isPublicHoliday = DateUtils::isPublicHoliday($date);

        // Then
        $this->assertTrue($isPublicHoliday, $name . " is not a public holiday.");
    }

    #[DataProvider("generateWorkingDays")]
    public function testWorkingDaysAreCorrect(string $dateAsString): void
    {
        // Given
        $date = new \DateTimeImmutable($dateAsString);

        // When
        $isWorkingDay = DateUtils::isWorkingDay($date);

        // Then
        $this->assertTrue($isWorkingDay, $dateAsString . " is not a working day.");
    }

    public function testGetNextWorkingDay(): void
    {
        // Given
        $date = "2022-01-01";
        $expected = "2022-01-03";

        // When
        $actual = DateUtils::getNextWorkingDay(new \DateTime($date));

        // Then
        $this->assertEquals($expected, DateUtils::format(DateUtils::ISO_DATE, $actual));
    }

    public function testGetPreviousWorkingDay(): void
    {
        // Given
        $date = "2022-06-07";
        $expected = "2022-06-03";

        // When
        $actual = DateUtils::getPreviousWorkingDay(new \DateTime($date));

        // Then
        $this->assertEquals($expected, DateUtils::format(DateUtils::ISO_DATE, $actual));
    }

    public function testDateAndTimeStringIsProperlyIdentifiedAsPublicHoliday(): void
    {
        // Given
        $ascension = "2022-05-26 15:41:23";

        // When
        $result = DateUtils::isPublicHoliday(new \DateTime($ascension));

        // Then
        $this->assertTrue($result);
    }

    /**
     * Generates public holidays in France for the year 2022,
     * as YYYY-MM-DD format.
     * 
     * @return \Generator<array<int, string>>
     */
    public static function generateFrenchPublicHolidaysFor2022(): \Generator
    {
        yield ["New Year's Day", "2022-01-01"];
        yield ["Easter Monday", "2022-04-18"];
        yield ["Labour Day", "2022-05-01"];
        yield ["Victory in Europe Day", "2022-05-08"];
        yield ["Ascension", "2022-05-26"];
        yield ["Pentecost Monday", "2022-06-06"];
        yield ["Bastille Day", "2022-07-14"];
        yield ["Assumption of Mary", "2022-08-15"];
        yield ["All Saints' Day", "2022-11-01"];
        yield ["Armistice Day", "2022-11-11"];
        yield ["Christmas Day", "2022-12-25"];
    }

    /**
     * Generates working days in France for the year 2022,
     * as YYYY-MM-DD format.
     * 
     * @return \Generator<array<int, string>>
     */
    public static function generateWorkingDays(): \Generator
    {
        yield ["2022-01-03"];
        yield ["2022-01-04"];
        yield ["2022-02-01"];
        yield ["2022-02-02"];
        yield ["2022-03-01"];
        yield ["2022-03-02"];
        yield ["2022-04-01"];
        yield ["2022-04-04"];
        yield ["2022-05-02"];
        yield ["2022-05-03"];
        yield ["2022-06-01"];
        yield ["2022-06-02"];
        yield ["2022-07-01"];
        yield ["2022-07-04"];
        yield ["2022-08-01"];
        yield ["2022-08-02"];
        yield ["2022-09-01"];
        yield ["2022-09-02"];
        yield ["2022-10-03"];
        yield ["2022-10-04"];
        yield ["2022-11-02"];
        yield ["2022-11-03"];
        yield ["2022-12-01"];
        yield ["2022-12-02"];
    }
}
