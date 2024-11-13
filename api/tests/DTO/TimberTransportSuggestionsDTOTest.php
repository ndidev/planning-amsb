<?php

// Path: api/tests/DTO/TimberTransportSuggestionsDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\TimberTransportSuggestionsDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberTransportSuggestionsDTO::class)]
final class TimberTransportSuggestionsDTOTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        // Given
        $loadingPlaceData = [
            'id' => 1,
            'cp' => '12345',
            'pays' => 'FR',
        ];

        $deliveryPlaceData = [
            'id' => 2,
            'cp' => '54321',
            'pays' => 'FR',
        ];

        $transportData = [
            [
                'transports' => 1,
                'nom' => 'Transporteur 1',
                'telephone' => '0123456789',
            ],
            [
                'transports' => 2,
                'nom' => 'Transporteur 2',
                'telephone' => '9876543210',
            ],
        ];

        $expectedOutput = [
            'chargement' => $loadingPlaceData,
            'livraison' => $deliveryPlaceData,
            'transporteurs' => $transportData,
        ];

        $dto = new TimberTransportSuggestionsDTO($loadingPlaceData, $deliveryPlaceData, $transportData);

        // When
        $dataToBeSerialized = $dto->jsonSerialize();

        // Then
        $this->assertSame($expectedOutput, $dataToBeSerialized);
    }
}
