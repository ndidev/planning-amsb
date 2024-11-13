<?php

// Path: api/tests/DTO/TidesDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\TidesDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TidesDTO::class)]
final class TidesDTOTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        // Given
        $tides = [
            [
                "date" => "2021-10-01",
                "heure" => "00:00:00",
                "te_cesson" => "1.0",
                "te_bassin" => "2",
            ],
            [
                "date" => "2021-10-01",
                "heure" => "06:00:00",
                "te_cesson" => 3.0,
                "te_bassin" => 4,
            ],
        ];

        $expectedOutput = [
            [
                "date" => "2021-10-01",
                "heure" => "00:00",
                "te_cesson" => 1.0,
                "te_bassin" => 2.0,
            ],
            [
                "date" => "2021-10-01",
                "heure" => "06:00",
                "te_cesson" => 3.0,
                "te_bassin" => 4.0,
            ],
        ];

        $tidesDTO = new TidesDTO($tides);

        // When
        $dataToBeSerialized = $tidesDTO->jsonSerialize();

        // Then
        $this->assertSame($expectedOutput, $dataToBeSerialized);
    }
}
