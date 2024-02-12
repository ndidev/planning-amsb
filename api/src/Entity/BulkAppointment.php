<?php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;
use DateTime;
use App\Service\{
    BulkProductService,
    ThirdPartyService,
};

class BulkAppointment implements Arrayable
{
    private ?int $id;
    private DateTime $date;
    private ?DateTime $time;
    private BulkProduct $product;
    private ?BulkQuality $quality;
    private int $quantity;
    private bool $max;
    private bool $ready;
    private ThirdParty $supplier;
    private ThirdParty $customer;
    private ?ThirdParty $transport;
    private string $orderNUmber;
    private string $comments;

    public function __construct(array $rawData = [])
    {
        $this->setId($rawData["id"] ?? null);
        $this->setDate($rawData["date_rdv"] ?? new DateTime("now"));
        $this->setTime($rawData["heure"] ?? null);
        $this->setProduct($rawData["produit"] ?? []);
        $this->setQuality($rawData["qualite"] ?? null);
        $this->setQuantity($rawData["quantite"] ?? 0);
        $this->setMax($rawData["max"] ?? false);
        $this->setReady($rawData["commande_prete"] ?? false);
        $this->setSupplier($rawData["fournisseur"] ?? []);
        $this->setCustomer($rawData["client"] ?? []);
        $this->setTransport($rawData["transporteur"] ?? null);
        $this->setOrderNumber($rawData["num_commande"] ?? "");
        $this->setComments($rawData["commentaire"] ?? "");
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime|string $date): static
    {
        if (is_string($date)) {
            $this->date = new DateTime($date);
        } else {
            $this->date = $date;
        }

        return $this;
    }

    public function getTime(): ?DateTime
    {
        return $this->time;
    }

    public function setTime(DateTime|string|null $time): static
    {
        if (is_null($time)) {
            $this->time = null;
        } else if (is_string($time)) {
            $this->time = new DateTime($time);
        } else {
            $this->time = $time;
        }

        return $this;
    }

    public function getProduct(): BulkProduct
    {
        return $this->product;
    }

    public function setProduct(BulkProduct|int $product): static
    {
        if (is_int($product)) {
            $this->product = (new BulkProductService())->getProduct($product);
        } else {
            $this->product = $product;
        }

        return $this;
    }

    public function getQuality(): ?BulkQuality
    {
        return $this->quality;
    }

    public function setQuality(BulkQuality|int|null $quality): static
    {
        if (is_null($quality)) {
            $this->quality = null;
        } else if (is_int($quality)) {
            $this->quality = (new BulkProductService())->getQuality($quality);
        } else {
            $this->quality = $quality;
        }

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getMax(): bool
    {
        return $this->max;
    }

    public function setMax(bool|int $max): static
    {
        $this->max = (bool) $max;

        return $this;
    }

    public function getReady(): bool
    {
        return $this->ready;
    }

    public function setReady(bool|int $ready): static
    {
        $this->ready = (bool) $ready;

        return $this;
    }

    public function getSupplier(): ThirdParty
    {
        return $this->supplier;
    }

    public function setSupplier(ThirdParty|int $supplier): static
    {
        if (is_int($supplier)) {
            $this->supplier = (new ThirdPartyService())->getThirdParty($supplier);
        } else {
            $this->supplier = $supplier;
        }

        return $this;
    }

    public function getCustomer(): ThirdParty
    {
        return $this->customer;
    }

    public function setCustomer(ThirdParty|int $customer): static
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

    public function setTransport(ThirdParty|int|null $transport): static
    {
        if (is_null($transport)) {
            $this->transport = null;
        } else if (is_int($transport)) {
            $this->transport = (new ThirdPartyService())->getThirdParty($transport);
        } else {
            $this->transport = $transport;
        }

        return $this;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNUmber;
    }

    public function setOrderNumber(string $orderNUmber): static
    {
        $this->orderNUmber = $orderNUmber;

        return $this;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function toArray(): array
    {
        return  [
            "id" => $this->getId(),
            "date_rdv" => $this->getDate()->format("Y-m-d"),
            "heure" => $this->getTime()?->format("H:i"),
            "produit" => $this->getProduct()->getId(),
            "qualite" => $this->getQuality()?->getId(),
            "quantite" => $this->getQuantity(),
            "max" => $this->getMax(),
            "commande_prete" => $this->getReady(),
            "fournisseur" => $this->getSupplier()->getId(),
            "client" => $this->getCustomer()->getId(),
            "transporteur" => $this->getTransport()?->getId(),
            "num_commande" => $this->getOrderNUmber(),
            "commentaire" => $this->getComments(),
        ];
    }
}
