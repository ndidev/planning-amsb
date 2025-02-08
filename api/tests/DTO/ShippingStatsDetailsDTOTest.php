<?php

// Path: api/tests/DTO/ShippingStatsDetailsDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\ShippingStatsDetailsDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShippingStatsDetailsDTO::class)]
final class ShippingStatsDetailsDTOTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        // Given
        $calls = [
            [
                "id" => 1,
                "navire" => "navire1",
                "ops_date" => "2021-01-01",
                "etc_date" => "2021-01-02",
                "marchandise" => "marchandise1",
                "client" => "client1",
                "tonnage_bl" => 1.0,
                "tonnage_outturn" => 2.0,
                "cubage_bl" => 3.0,
                "cubage_outturn" => 4.0,
                "nombre_bl" => 5.0,
                "nombre_outturn" => 6.0,
            ],
            [
                "id" => 1,
                "navire" => "navire1",
                "ops_date" => "2021-01-01",
                "etc_date" => "2021-01-02",
                "marchandise" => "marchandise2",
                "client" => "client2",
                "tonnage_bl" => 7.0,
                "tonnage_outturn" => 8.0,
                "cubage_bl" => 9.0,
                "cubage_outturn" => null,
                "nombre_bl" => null,
                "nombre_outturn" => null,
            ],
            [
                "id" => 2,
                "navire" => "navire2",
                "ops_date" => null,
                "etc_date" => null,
                "marchandise" => "marchandise3",
                "client" => "client3",
                "tonnage_bl" => 13.0,
                "tonnage_outturn" => 14.0,
                "cubage_bl" => 15.0,
                "cubage_outturn" => 16.0,
                "nombre_bl" => 17.0,
                "nombre_outturn" => 18.0,
            ],
        ];

        $expectedOutput = [
            [
                "id" => 1,
                "navire" => "navire1",
                "ops_date" => "2021-01-01",
                "etc_date" => "2021-01-02",
                "marchandises" => [
                    [
                        "marchandise" => "marchandise1",
                        "client" => "client1",
                        "tonnage_outturn" => 2.0,
                        "cubage_outturn" => 4.0,
                        "nombre_outturn" => 6.0,
                    ],
                    [
                        "marchandise" => "marchandise2",
                        "client" => "client2",
                        "tonnage_outturn" => 8.0,
                        "cubage_outturn" => 9.0,
                        "nombre_outturn" => null,
                    ],
                ],
            ],
            [
                "id" => 2,
                "navire" => "navire2",
                "ops_date" => null,
                "etc_date" => null,
                "marchandises" => [
                    [
                        "marchandise" => "marchandise3",
                        "client" => "client3",
                        "tonnage_outturn" => 14.0,
                        "cubage_outturn" => 16.0,
                        "nombre_outturn" => 18.0,
                    ],
                ],
            ],
        ];

        $shippingStatsDetailsDTO = new ShippingStatsDetailsDTO($calls);

        // When
        $dataToBeSerialized = $shippingStatsDetailsDTO->jsonSerialize();

        // Then
        $this->assertSame($expectedOutput, $dataToBeSerialized);
    }
}
