<?php

// Path: api/tests/DTO/StevedoringDispatchDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\StevedoringDispatchDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StevedoringDispatchDTO::class)]
final class StevedoringDispatchDTOTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        // Given
        $bulkDispatchData = [
            [
                "date" => "2021-01-01",
                "staffName" => "Staff 1",
                "staffContractType" => "interim",
                "staffTempWorkAgency" => "Agency 1",
                "remarks" => "Remarks 1",
                "productName" => "Product 1",
                "qualityName" => "Quality 1",
            ],
            [
                "date" => "2021-01-01",
                "staffName" => "Staff 2",
                "staffContractType" => "mensuel",
                "staffTempWorkAgency" => "",
                "remarks" => "Remarks 2",
                "productName" => "Product 2",
                "qualityName" => "Quality 2",
            ],
            [
                "date" => "2021-01-02",
                "staffName" => "Staff 1",
                "staffContractType" => "interim",
                "staffTempWorkAgency" => "Agency 1",
                "remarks" => "Remarks 3",
                "productName" => "Product 3",
                "qualityName" => "Quality 3",
            ],
            [
                "date" => "2021-01-02",
                "staffName" => "Staff 3",
                "staffContractType" => "mensuel",
                "staffTempWorkAgency" => "",
                "remarks" => "",
                "productName" => "Product 1",
                "qualityName" => "Quality 2",
            ],
            [
                "date" => "2021-01-02",
                "staffName" => "Staff 4",
                "staffContractType" => "interim",
                "staffTempWorkAgency" => "Agency 2",
                "remarks" => "Remarks 5",
                "productName" => "Product 2",
                "qualityName" => "Quality 1",
            ],
        ];

        $expectedOutput = [
            "2021-01-01" => [
                "interim" => [
                    "Staff 1" => [
                        "tempWorkAgency" => "Agency 1",
                        "bulk" => [
                            [
                                "product" => "Product 1",
                                "quality" => "Quality 1",
                                "remarks" => "Remarks 1",
                            ],
                        ],
                    ],
                ],
                "mensuel" => [
                    "Staff 2" => [
                        "tempWorkAgency" => "",
                        "bulk" => [
                            [
                                "product" => "Product 2",
                                "quality" => "Quality 2",
                                "remarks" => "Remarks 2",
                            ],
                        ],
                    ],
                ],
            ],
            "2021-01-02" => [
                "interim" => [
                    "Staff 1" => [
                        "tempWorkAgency" => "Agency 1",
                        "bulk" => [
                            [
                                "product" => "Product 3",
                                "quality" => "Quality 3",
                                "remarks" => "Remarks 3",
                            ],
                        ],
                    ],
                    "Staff 4" => [
                        "tempWorkAgency" => "Agency 2",
                        "bulk" => [
                            [
                                "product" => "Product 2",
                                "quality" => "Quality 1",
                                "remarks" => "Remarks 5",
                            ],
                        ],
                    ],
                ],
                "mensuel" => [
                    "Staff 3" => [
                        "tempWorkAgency" => "",
                        "bulk" => [
                            [
                                "product" => "Product 1",
                                "quality" => "Quality 2",
                                "remarks" => "",
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $StevedoringDispatchDTO = new StevedoringDispatchDTO($bulkDispatchData);

        // When
        $dataToBeSerialized = $StevedoringDispatchDTO->jsonSerialize();

        // Then
        $this->assertSame($expectedOutput, $dataToBeSerialized);
    }
}
