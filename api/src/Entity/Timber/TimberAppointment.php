<?php

// Path: api/src/Entity/Timber/TimberAppointment.php

namespace App\Entity\Timber;

use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\ThirdParty;
use App\Service\ThirdPartyService;

class TimberAppointment extends AbstractEntity
{
    use IdentifierTrait;

    private bool $isOnHold = false;
    private ?\DateTimeImmutable $date = null;
    private ?\DateTimeImmutable $arrivalTime = null;
    private ?\DateTimeImmutable $departureTime = null;
    private ?ThirdParty $supplier = null;
    private ?ThirdParty $loadingPlace = null;
    private ?ThirdParty $deliveryPlace = null;
    private ?ThirdParty $customer = null;
    private ?ThirdParty $transport = null;
    private ?ThirdParty $transportBroker = null;
    private bool $isReady = false;
    private bool $charteringConfirmationSent = false;
    private string $deliveryNoteNumber = "";
    private string $publicComment = "";
    private string $privateComment = "";

    public function isOnHold(): bool
    {
        return $this->isOnHold;
    }

    public function setOnHold(bool|int $attente): static
    {
        $this->isOnHold = (bool) $attente;

        return $this;
    }

    public function getDate(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->date->format("Y-m-d");
        } else {
            return $this->date;
        }
    }

    public function setDate(\DateTimeImmutable|string|null $date): static
    {
        if (is_string($date)) {
            $this->date = new \DateTimeImmutable($date);
        } else {
            $this->date = $date;
        }

        return $this;
    }

    public function getArrivalTime(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->arrivalTime?->format("H:i");
        } else {
            return $this->arrivalTime;
        }
    }

    public function setArrivalTime(\DateTimeImmutable|string|null $arrivalTime): static
    {
        if (is_string($arrivalTime)) {
            $this->arrivalTime = new \DateTimeImmutable($arrivalTime);
        } else {
            $this->arrivalTime = $arrivalTime;
        }

        return $this;
    }

    public function getDepartureTime(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->departureTime?->format("H:i");
        } else {
            return $this->departureTime;
        }
    }

    public function setDepartureTime(\DateTimeImmutable|string|null $departureTime): static
    {
        if (is_string($departureTime)) {
            $this->departureTime = new \DateTimeImmutable($departureTime);
        } else {
            $this->departureTime = $departureTime;
        }

        return $this;
    }

    public function getSupplier(): ?ThirdParty
    {
        return $this->supplier;
    }

    public function setSupplier(ThirdParty|int|null $supplier): static
    {
        if (is_int($supplier)) {
            $this->supplier = (new ThirdPartyService())->getThirdParty($supplier);
        } else {
            $this->supplier = $supplier;
        }

        return $this;
    }

    public function getLoadingPlace(): ?ThirdParty
    {
        return $this->loadingPlace;
    }

    public function setLoadingPlace(ThirdParty|int|null $loadingPlace): static
    {
        if (is_int($loadingPlace)) {
            $this->loadingPlace = (new ThirdPartyService())->getThirdParty($loadingPlace);
        } else {
            $this->loadingPlace = $loadingPlace;
        }

        return $this;
    }

    public function getDeliveryPlace(): ?ThirdParty
    {
        return $this->deliveryPlace;
    }

    public function setDeliveryPlace(ThirdParty|int|null $livraison): static
    {
        if (is_int($livraison)) {
            $this->deliveryPlace = (new ThirdPartyService())->getThirdParty($livraison);
        } else {
            $this->deliveryPlace = $livraison;
        }

        return $this;
    }

    public function getCustomer(): ?ThirdParty
    {
        return $this->customer;
    }

    public function setCustomer(ThirdParty|int|null $customer): static
    {
        if (is_int($customer)) {
            $this->customer = (new ThirdPartyService())->getThirdParty($customer);
        } else {
            $this->customer = $customer;
        }

        return $this;
    }

    public function getTransport(): ?ThirdParty
    {
        return $this->transport;
    }

    public function setTransport(ThirdParty|int|null $transporteur): static
    {
        if (is_int($transporteur)) {
            $this->transport = (new ThirdPartyService())->getThirdParty($transporteur);
        } else {
            $this->transport = $transporteur;
        }

        return $this;
    }

    public function getTransportBroker(): ?ThirdParty
    {
        return $this->transportBroker;
    }

    public function setTransportBroker(ThirdParty|int|null $transportBroker): static
    {
        if (is_int($transportBroker)) {
            $this->transportBroker = (new ThirdPartyService())->getThirdParty($transportBroker);
        } else {
            $this->transportBroker = $transportBroker;
        }

        return $this;
    }

    public function isReady(): bool
    {
        return $this->isReady;
    }

    public function setReady(bool|int $isReady): static
    {
        $this->isReady = (bool) $isReady;

        return $this;
    }

    public function getCharteringConfirmationSent(): bool
    {
        return $this->charteringConfirmationSent;
    }

    public function setCharteringConfirmationSent(bool|int $charteringConfirmationSent): static
    {
        $this->charteringConfirmationSent = (bool) $charteringConfirmationSent;

        return $this;
    }

    public function getDeliveryNoteNumber(): string
    {
        return $this->deliveryNoteNumber;
    }

    public function setDeliveryNoteNumber(string $deliveryNoteNumber): static
    {
        $this->deliveryNoteNumber = $deliveryNoteNumber;

        return $this;
    }

    public function getPublicComment(): string
    {
        return $this->publicComment;
    }

    public function setPublicComment(string $publicComment): static
    {
        $this->publicComment = $publicComment;

        return $this;
    }

    public function getPrivateComment(): string
    {
        return $this->privateComment;
    }

    public function setPrivateComment(string $privateComment): static
    {
        $this->privateComment = $privateComment;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "attente" => $this->isOnHold(),
            "date_rdv" => $this->getDate(true),
            "heure_arrivee" => $this->getArrivalTime(true),
            "heure_depart" => $this->getDepartureTime(true),
            "fournisseur" => $this->getSupplier()?->getId(),
            "chargement" => $this->getLoadingPlace()?->getId(),
            "livraison" => $this->getDeliveryPlace()?->getId(),
            "client" => $this->getCustomer()?->getId(),
            "transporteur" => $this->getTransport()?->getId(),
            "affreteur" => $this->getTransportBroker()?->getId(),
            "commande_prete" => $this->isReady(),
            "confirmation_affretement" => $this->getCharteringConfirmationSent(),
            "numero_bl" => $this->getDeliveryNoteNumber(),
            "commentaire_public" => $this->getPublicComment(),
            "commentaire_cache" => $this->getPrivateComment(),
        ];
    }
}
