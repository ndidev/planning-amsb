<?php

// Path: api/src/Entity/Bulk/BulkAppointment.php

declare(strict_types=1);

namespace App\Entity\Bulk;

use App\Core\Component\DateUtils;
use App\Entity\AbstractEntity;
use App\Entity\ThirdParty;
use App\Core\Traits\IdentifierTrait;

class BulkAppointment extends AbstractEntity
{
    use IdentifierTrait;

    private ?\DateTimeImmutable $date = null;
    private ?\DateTimeImmutable $time = null;
    private ?BulkProduct $product = null;
    private ?BulkQuality $quality = null;
    private ?BulkQuantity $quantity = null;
    private bool $ready = false;
    private ?ThirdParty $supplier = null;
    private ?ThirdParty $customer = null;
    private ?ThirdParty $carrier = null;
    private string $orderNumber = "";
    private string $comments = "";

    public function setDate(\DateTimeInterface|string $date): static
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
        return $this->date?->format('Y-m-d') ?? null;
    }

    public function setTime(\DateTimeInterface|string|null $time): static
    {
        $this->time = DateUtils::makeDateTimeImmutable($time);

        return $this;
    }

    public function getTime(): ?\DateTimeImmutable
    {
        return $this->time;
    }

    public function getSqlTime(): ?string
    {
        return $this->time?->format('H:i') ?? null;
    }

    public function setProduct(?BulkProduct $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?BulkProduct
    {
        return $this->product;
    }

    public function setQuality(?BulkQuality $qualite): static
    {
        $this->quality = $qualite;

        return $this;
    }

    public function getQuality(): ?BulkQuality
    {
        return $this->quality;
    }

    public function setQuantity(int $value, bool $max): static
    {
        $this->quantity ??= new BulkQuantity();

        $this->quantity->setValue($value)->setMax($max);

        return $this;
    }

    public function getQuantity(): ?BulkQuantity
    {
        return $this->quantity;
    }

    public function setReady(bool|int $commandePrete): static
    {
        $this->ready = (bool) $commandePrete;

        return $this;
    }

    public function isReady(): bool
    {
        return $this->ready;
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

    public function setOrderNumber(string $orderNUmber): static
    {
        $this->orderNumber = $orderNUmber;

        return $this;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function toArray(): array
    {
        return  [
            "id" => $this->getId(),
            "date_rdv" => $this->getDate()?->format('Y-m-d'),
            "heure" => $this->getTime()?->format('H:i'),
            "produit" => $this->getProduct()?->getId(),
            "qualite" => $this->getQuality()?->getId(),
            "quantite" => $this->getQuantity()?->getValue() ?? 0,
            "max" => $this->getQuantity()?->isMax() ?? false,
            "commande_prete" => $this->isReady(),
            "fournisseur" => $this->getSupplier()?->getId(),
            "client" => $this->getCustomer()?->getId(),
            "transporteur" => $this->getCarrier()?->getId(),
            "num_commande" => $this->getOrderNumber(),
            "commentaire" => $this->getComments(),
        ];
    }
}
