<?php

// Path: api/src/Entity/Timber/TimberAppointment.php

declare(strict_types=1);

namespace App\Entity\Timber;

use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\ThirdParty;

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
    private ?ThirdParty $carrier = null;
    private ?ThirdParty $transportBroker = null;
    private bool $isReady = false;
    private bool $charteringConfirmationSent = false;
    private string $deliveryNoteNumber = "";
    private string $publicComment = "";
    private string $privateComment = "";

    public function setOnHold(bool|int $attente): static
    {
        $this->isOnHold = (bool) $attente;

        return $this;
    }

    public function isOnHold(): bool
    {
        return $this->isOnHold;
    }

    public function setDate(\DateTimeImmutable|string|null $date): static
    {
        $this->date = DateUtils::makeDateTimeImmutable($date);

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function getSqlDate(): ?string
    {
        return $this->date?->format('Y-m-d');
    }

    public function setArrivalTime(\DateTimeImmutable|string|null $arrivalTime): static
    {
        $this->arrivalTime = DateUtils::makeDateTimeImmutable($arrivalTime);

        return $this;
    }

    public function getArrivalTime(): ?\DateTimeImmutable
    {
        return $this->arrivalTime;
    }

    public function getSqlArrivalTime(): ?string
    {
        return $this->arrivalTime?->format('H:i');
    }

    public function setDepartureTime(\DateTimeImmutable|string|null $departureTime): static
    {
        $this->departureTime = DateUtils::makeDateTimeImmutable($departureTime);

        return $this;
    }

    public function getDepartureTime(): ?\DateTimeImmutable
    {
        return $this->departureTime;
    }

    public function getSqlDepartureTime(): ?string
    {
        return $this->departureTime?->format('H:i');
    }

    public function setSupplier(?ThirdParty $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSupplier(): ?ThirdParty
    {
        return $this->supplier;
    }

    public function setLoadingPlace(?ThirdParty $loadingPlace): static
    {
        $this->loadingPlace = $loadingPlace;

        return $this;
    }

    public function getLoadingPlace(): ?ThirdParty
    {
        return $this->loadingPlace;
    }

    public function setDeliveryPlace(?ThirdParty $deliveryPlace): static
    {
        $this->deliveryPlace = $deliveryPlace;

        return $this;
    }

    public function getDeliveryPlace(): ?ThirdParty
    {
        return $this->deliveryPlace;
    }

    public function setCustomer(?ThirdParty $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomer(): ?ThirdParty
    {
        return $this->customer;
    }

    public function setCarrier(?ThirdParty $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function getCarrier(): ?ThirdParty
    {
        return $this->carrier;
    }

    public function setTransportBroker(?ThirdParty $transportBroker): static
    {
        $this->transportBroker = $transportBroker;

        return $this;
    }

    public function getTransportBroker(): ?ThirdParty
    {
        return $this->transportBroker;
    }

    public function setReady(bool|int $isReady): static
    {
        $this->isReady = (bool) $isReady;

        return $this;
    }

    public function isReady(): bool
    {
        return $this->isReady;
    }

    public function setCharteringConfirmationSent(bool|int $charteringConfirmationSent): static
    {
        $this->charteringConfirmationSent = (bool) $charteringConfirmationSent;

        return $this;
    }

    public function isCharteringConfirmationSent(): bool
    {
        return $this->charteringConfirmationSent;
    }

    public function setDeliveryNoteNumber(string $deliveryNoteNumber): static
    {
        $this->deliveryNoteNumber = $deliveryNoteNumber;

        return $this;
    }

    public function getDeliveryNoteNumber(): string
    {
        return $this->deliveryNoteNumber;
    }

    public function setPublicComment(string $publicComment): static
    {
        $this->publicComment = $publicComment;

        return $this;
    }

    public function getPublicComment(): string
    {
        return $this->publicComment;
    }

    public function setPrivateComment(string $privateComment): static
    {
        $this->privateComment = $privateComment;

        return $this;
    }

    public function getPrivateComment(): string
    {
        return $this->privateComment;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "attente" => $this->isOnHold(),
            "date_rdv" => $this->getDate()?->format('Y-m-d'),
            "heure_arrivee" => $this->getArrivalTime()?->format('H:i'),
            "heure_depart" => $this->getDepartureTime()?->format('H:i'),
            "fournisseur" => $this->getSupplier()?->getId(),
            "chargement" => $this->getLoadingPlace()?->getId(),
            "livraison" => $this->getDeliveryPlace()?->getId(),
            "client" => $this->getCustomer()?->getId(),
            "transporteur" => $this->getCarrier()?->getId(),
            "affreteur" => $this->getTransportBroker()?->getId(),
            "commande_prete" => $this->isReady(),
            "confirmation_affretement" => $this->isCharteringConfirmationSent(),
            "numero_bl" => $this->getDeliveryNoteNumber(),
            "commentaire_public" => $this->getPublicComment(),
            "commentaire_cache" => $this->getPrivateComment(),
        ];
    }
}
