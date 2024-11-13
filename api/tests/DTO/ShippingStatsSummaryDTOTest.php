<?php

// Path: api/tests/DTO/ShippingStatsSummaryDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\ShippingStatsSummaryDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShippingStatsSummaryDTO::class)]
final class ShippingStatsSummaryDTOTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        // Given
        $statsSummaryRaw = [
            ['id' => 1, 'date' => '2021-01-01'],
            ['id' => 2, 'date' => '2021-02-01'],
            ['id' => 3, 'date' => '2021-03-01'],
            ['id' => 4, 'date' => '2021-04-01'],
            ['id' => 5, 'date' => '2021-04-01'],
            ['id' => 6, 'date' => '2022-01-01'],
            ['id' => 7, 'date' => '2022-01-01'],
            ['id' => 8, 'date' => '2022-02-01'],
            ['id' => 9, 'date' => '2022-03-01'],
            ['id' => 10, 'date' => '2022-04-01'],
            ['id' => 11, 'date' => '2022-05-01'],
            ['id' => 12, 'date' => '2022-06-01'],
        ];

        $expectedOutput = [
            'Total' => 12,
            'ByYear' => [
                2021 => [
                    1 => ['nombre' => 1, 'ids' => [1]],
                    2 => ['nombre' => 1, 'ids' => [2]],
                    3 => ['nombre' => 1, 'ids' => [3]],
                    4 => ['nombre' => 2, 'ids' => [4, 5]],
                    5 => ['nombre' => 0, 'ids' => []],
                    6 => ['nombre' => 0, 'ids' => []],
                    7 => ['nombre' => 0, 'ids' => []],
                    8 => ['nombre' => 0, 'ids' => []],
                    9 => ['nombre' => 0, 'ids' => []],
                    10 => ['nombre' => 0, 'ids' => []],
                    11 => ['nombre' => 0, 'ids' => []],
                    12 => ['nombre' => 0, 'ids' => []],
                ],
                2022 => [
                    1 => ['nombre' => 2, 'ids' => [6, 7]],
                    2 => ['nombre' => 1, 'ids' => [8]],
                    3 => ['nombre' => 1, 'ids' => [9]],
                    4 => ['nombre' => 1, 'ids' => [10]],
                    5 => ['nombre' => 1, 'ids' => [11]],
                    6 => ['nombre' => 1, 'ids' => [12]],
                    7 => ['nombre' => 0, 'ids' => []],
                    8 => ['nombre' => 0, 'ids' => []],
                    9 => ['nombre' => 0, 'ids' => []],
                    10 => ['nombre' => 0, 'ids' => []],
                    11 => ['nombre' => 0, 'ids' => []],
                    12 => ['nombre' => 0, 'ids' => []],
                ],
            ],
        ];

        $shippingStatsSummaryDTO = new ShippingStatsSummaryDTO($statsSummaryRaw);

        // When
        $dataToBeSerialized = $shippingStatsSummaryDTO->jsonSerialize();

        // Then
        $this->assertSame($expectedOutput, $dataToBeSerialized);
    }
}
