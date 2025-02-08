<?php

// Path: api/src/DTO/TimberStatsDTO.php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\TimberStatsDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberStatsDTO::class)]
final class TimberStatsDTOTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        // Given
        $dates = [
            "2021-01-01",
            "2021-01-02",
            "2021-02-01",
            "2021-02-02",
            "2021-02-03",
            "2021-03-01",
            "2021-04-01",
            "2021-04-02",
            "2021-05-01",
            "2021-05-02",
            "2021-05-03",
            "2021-06-01",
            "2023-01-01",
            "2023-02-01",
            "2023-02-03",
            "2023-03-01",
            "2023-03-02",
            "2023-04-01",
            "2023-04-02",
            "2023-05-01",
            "2023-05-02",
            "2023-05-03",
            "2023-06-01",
        ];

        $expectedOutput = [
            'Total' => 23,
            'ByYear' => [
                '2021' => [
                    1 => 2,
                    2 => 3,
                    3 => 1,
                    4 => 2,
                    5 => 3,
                    6 => 1,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    10 => 0,
                    11 => 0,
                    12 => 0,
                ],
                '2023' => [
                    1 => 1,
                    2 => 2,
                    3 => 2,
                    4 => 2,
                    5 => 3,
                    6 => 1,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    10 => 0,
                    11 => 0,
                    12 => 0,
                ],
            ],
        ];

        // When
        $timberStatsDTO = new TimberStatsDTO($dates);
        $dataToBeSerialized = $timberStatsDTO->jsonSerialize();

        // Then
        $this->assertSame($expectedOutput, $dataToBeSerialized);
    }
}
