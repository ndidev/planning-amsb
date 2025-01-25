<?php

// Path: api/tests/DTO/TempWorkDispatchForDateDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\TempWorkDispatchForDateDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TempWorkDispatchForDateDTO::class)]
final class TempWorkDispatchForDateDTOTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        // Given
        $dispatchData = [
            // Bulk
            [
                "id" => "1",
                "hoursWorked" => "0",
            ],
            // Timber
            [
                "id" => "2",
                "hoursWorked" => "0",
            ],
            // Ship
            [
                "id" => "1",
                "hoursWorked" => "8",
            ],
            [
                "id" => "2",
                "hoursWorked" => "8",
            ],
            [
                "id" => "3",
                "hoursWorked" => "7.5",
            ],
        ];

        $expectedOutput = [
            1 => 0.0,
            2 => 0.0,
            3 => 7.5,
        ];

        $StevedoringDispatchDTO = new TempWorkDispatchForDateDTO($dispatchData);

        // When
        $dataToBeSerialized = $StevedoringDispatchDTO->jsonSerialize();

        // Then
        $this->assertSame($expectedOutput, $dataToBeSerialized);
    }
}
