<?php

// Path: api/src/DTO/TimberRegistryEntryDTO.php

declare(strict_types=1);

namespace App\DTO;

use App\Core\Component\DateUtils;

/**
 * @phpstan-type TimberRegistryEntryArray array{
 *                                          date_rdv: string,
 *                                          fournisseur: string|null,
 *                                          chargement_nom: string|null,
 *                                          chargement_ville: string|null,
 *                                          chargement_pays: string|null,
 *                                          livraison_nom: string|null,
 *                                          livraison_cp: string|null,
 *                                          livraison_ville: string|null,
 *                                          livraison_pays: string|null,
 *                                          numero_bl: string,
 *                                          transporteur: string|null,
 *                                        }
 */
final class TimberRegistryEntryDTO
{
    public string $date {
        set {
            $this->date = $value;
            $this->timestamp = strtotime($value) ?: null;
        }
        get => $this->timestamp ? date('d/m/Y', $this->timestamp) : $this->date;
    }

    private ?int $timestamp = null;

    public string $month {
        get => $this->timestamp ? DateUtils::format("LLLL", new \DateTime("@{$this->timestamp}")) : '';
    }

    public string $supplierName = '';

    public string $loadingPlaceName = '';

    public string $loadingPlaceCity = '';

    public string $loadingPlaceCountry = '';

    public string $deliveryPlaceName = '';

    public string $deliveryPlacePostCode = '';

    public string $deliveryPlaceCity = '';

    public string $deliveryPlaceCountry = '';

    public string $deliveryNoteNumber = '';

    public string $carrier = '';

    public function getLoadingPlace(): string
    {
        if ($this->loadingPlaceName === 'AMSB') {
            return 'AMSB';
        } else {
            return $this->loadingPlaceName
                . ' '
                . $this->loadingPlaceCity
                . (\mb_strtolower($this->loadingPlaceCountry) == 'france'
                    ? ''
                    : " ({$this->loadingPlaceCountry})");
        }
    }

    public function getDeliveryPlace(): string
    {
        if ($this->deliveryPlaceName) {
            if (\mb_strtolower($this->deliveryPlaceCountry) === 'france') {
                $livraison_departement = ' ' . \mb_substr($this->deliveryPlacePostCode, 0, 2);
                $this->deliveryPlaceCountry = '';
            } else {
                $livraison_departement = '';
                $this->deliveryPlaceCountry = " ({$this->deliveryPlaceCountry})";
            }

            return $this->deliveryPlaceName
                . $livraison_departement
                . ' '
                . $this->deliveryPlaceCity
                . $this->deliveryPlaceCountry;
        } else {
            return "Pas de lieu de livraison renseigné";
        }
    }
}
