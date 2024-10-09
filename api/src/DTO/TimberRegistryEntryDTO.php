<?php

namespace App\DTO;

use App\Core\Component\DateUtils;

class TimberRegistryEntryDTO
{
    private string $date;
    private ?string $supplierName = null;
    private ?string $loadingPlaceName = null;
    private ?string $loadingPlaceCity = null;
    private ?string $loadingPlaceCountry = null;
    private ?string $deliveryPlaceName = null;
    private ?string $deliveryPlacePostCode = null;
    private ?string $deliveryPlaceCity = null;
    private ?string $deliveryPlaceCountry = null;
    private string $deliveryNoteNumber = "";
    private ?string $transport = null;

    public function setDate(string $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): string
    {
        return date('d/m/Y', strtotime($this->date));
    }

    public function getMonth(): string
    {
        return DateUtils::format("LLLL", new \DateTime($this->date));
    }

    public function setSupplierName(?string $supplierName): static
    {
        $this->supplierName = $supplierName;

        return $this;
    }

    public function getSupplierName(): string
    {
        return (string) $this->supplierName;
    }

    public function setLoadingPlaceName(?string $loadingPlaceName): static
    {
        $this->loadingPlaceName = $loadingPlaceName;

        return $this;
    }

    public function getLoadingPlaceName(): string
    {
        return (string) $this->loadingPlaceName;
    }

    public function setLoadingPlaceCity(?string $loadingPlaceCity): static
    {
        $this->loadingPlaceCity = $loadingPlaceCity;

        return $this;
    }

    public function getLoadingPlaceCity(): string
    {
        return (string) $this->loadingPlaceCity;
    }

    public function setLoadingPlaceCountry(?string $loadingPlaceCountry): static
    {
        $this->loadingPlaceCountry = $loadingPlaceCountry;

        return $this;
    }

    public function getLoadingPlaceCountry(): string
    {
        return (string) $this->loadingPlaceCountry;
    }

    public function setDeliveryPlaceName(?string $deliveryPlaceName): static
    {
        $this->deliveryPlaceName = $deliveryPlaceName;

        return $this;
    }

    public function getDeliveryPlaceName(): string
    {
        return (string) $this->deliveryPlaceName;
    }

    public function setDeliveryPlacePostCode(?string $deliveryPlacePostCode): static
    {
        $this->deliveryPlacePostCode = $deliveryPlacePostCode;

        return $this;
    }

    public function getDeliveryPlacePostCode(): string
    {
        return (string) $this->deliveryPlacePostCode;
    }

    public function setDeliveryPlaceCity(?string $deliveryPlaceCity): static
    {
        $this->deliveryPlaceCity = $deliveryPlaceCity;

        return $this;
    }

    public function getDeliveryPlaceCity(): string
    {
        return (string) $this->deliveryPlaceCity;
    }

    public function setDeliveryPlaceCountry(?string $deliveryPlaceCountry): static
    {
        $this->deliveryPlaceCountry = $deliveryPlaceCountry;

        return $this;
    }

    public function getDeliveryPlaceCountry(): string
    {
        return (string) $this->deliveryPlaceCountry;
    }

    public function setDeliveryNoteNumber(string $deliveryNoteNumber): static
    {
        $this->deliveryNoteNumber = $deliveryNoteNumber;

        return $this;
    }

    public function getDeliveryNoteNumber(): string
    {
        return (string) $this->deliveryNoteNumber;
    }

    public function setTransport(?string $transport): static
    {
        $this->transport = $transport;

        return $this;
    }

    public function getTransport(): string
    {
        return (string) $this->transport;
    }

    public function getLoadingPlace(): string
    {
        if ($this->loadingPlaceName === "AMSB") {
            return "AMSB";
        } else {
            return $this->loadingPlaceName
                . ' '
                . $this->loadingPlaceCity
                . (strtolower($this->loadingPlaceCountry) == 'france'
                    ? ""
                    : " ({$this->loadingPlaceCountry})");
        }
    }

    public function getDeliveryPlace(): string
    {
        if ($this->deliveryPlaceName) {
            if (strtolower($this->deliveryPlaceCountry) === 'france') {
                $livraison_departement = " " . substr($this->deliveryPlacePostCode, 0, 2);
                $this->deliveryPlaceCountry = "";
            } else {
                $livraison_departement = "";
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
