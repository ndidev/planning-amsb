<?php

// Path: api/src/Entity/Bulk/BulkAppointment.php

namespace App\Entity\Bulk;

use App\Entity\AbstractEntity;
use App\Entity\ThirdParty;
use App\Service\BulkService;
use App\Service\ThirdPartyService;
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
    private ?ThirdParty $client = null;
    private ?ThirdParty $transport = null;
    private string $orderNumber = "";
    private string $comments = "";

    public function setDate(\DateTimeImmutable|string $date): static
    {
        if (is_string($date)) {
            $this->date = new \DateTimeImmutable($date);
        } else {
            $this->date = $date;
        }

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

    public function setTime(\DateTimeImmutable|string|null $heure): static
    {
        if (is_string($heure)) {
            $this->time = new \DateTimeImmutable($heure);
        } else {
            $this->time = $heure;
        }

        return $this;
    }

    public function getTime(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->time?->format("H:i");
        } else {
            return $this->time;
        }
    }

    public function setProduct(BulkProduct|int|null $product): static
    {
        if (is_int($product)) {
            $this->product = (new BulkService())->getProduct($product);
        } else {
            $this->product = $product;
        }

        return $this;
    }

    public function getProduct(): ?BulkProduct
    {
        return $this->product;
    }

    public function setQuality(BulkQuality|int|null $qualite): static
    {
        if (is_int($qualite)) {
            $this->quality = (new BulkService())->getQuality($qualite);
        } else {
            $this->quality = $qualite;
        }

        return $this;
    }

    public function getQuality(): ?BulkQuality
    {
        return $this->quality;
    }

    public function setQuantity(int $value, bool $max): static
    {
        if (!isset($this->quantity)) {
            $this->quantity = new BulkQuantity($value, $max);
        } else {
            $this->quantity->setValue($value)->setMax($max);
        }

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

    public function setSupplier(ThirdParty|int|null $supplier): static
    {
        if (is_int($supplier)) {
            $this->supplier = (new ThirdPartyService())->getThirdParty($supplier);
        } else {
            $this->supplier = $supplier;
        }

        return $this;
    }

    public function getSupplier(): ?ThirdParty
    {
        return $this->supplier;
    }

    public function setClient(ThirdParty|int|null $client): static
    {
        if (is_int($client)) {
            $this->client = (new ThirdPartyService())->getThirdParty($client);
        } else {
            $this->client = $client;
        }

        return $this;
    }

    public function getClient(): ?ThirdParty
    {
        return $this->client;
    }

    public function setTransport(ThirdParty|int|null $transport): static
    {
        if (is_int($transport)) {
            $this->transport = (new ThirdPartyService())->getThirdParty($transport);
        } else {
            $this->transport = $transport;
        }

        return $this;
    }

    public function getTransport(): ?ThirdParty
    {
        return $this->transport;
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
            "date_rdv" => $this->getDate()?->format("Y-m-d"),
            "heure" => $this->getTime()?->format("H:i"),
            "produit" => $this->getProduct()?->getId(),
            "qualite" => $this->getQuality()?->getId(),
            "quantite" => $this->getQuantity()?->getValue() ?? 0,
            "max" => $this->getQuantity()?->isMax() ?? false,
            "commande_prete" => $this->isReady(),
            "fournisseur" => $this->getSupplier()?->getId(),
            "client" => $this->getClient()?->getId(),
            "transporteur" => $this->getTransport()?->getId(),
            "num_commande" => $this->getOrderNumber(),
            "commentaire" => $this->getComments(),
        ];
    }
}
