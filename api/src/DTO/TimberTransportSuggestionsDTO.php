<?php

// Path: api/src/DTO/TimberTransportSuggestionsDTO.php

declare(strict_types=1);

namespace App\DTO;

final class TimberTransportSuggestionsDTO implements \JsonSerializable
{
    /**
     * @param array{id: int, cp: string, pays: string} $loadingPlaceData 
     * @param array{id: int, cp: string, pays: string} $deliveryPlaceData 
     * @param list<array{transports: int, nom: string, telephone: string}> $transportData 
     * @return void 
     */
    public function __construct(
        private array $loadingPlaceData,
        private array $deliveryPlaceData,
        private array $transportData,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'chargement' => $this->loadingPlaceData,
            'livraison' => $this->deliveryPlaceData,
            'transporteurs' => $this->transportData,
        ];
    }
}
