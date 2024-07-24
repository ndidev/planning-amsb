<?php

// path: api/src/Entity/Chartering/CharterLeg.php

namespace App\Entity\Chartering;

use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\Port;

class CharterLeg extends AbstractEntity
{
    use IdentifierTrait;

    private ?Charter $charter = null;
    private ?\DateTimeImmutable $blDate = null;
    private ?Port $pol = null;
    private ?Port $pod = null;
    private string $commodity = '';
    private string $quantity = '';
    private string $comments = '';

    public function getCharter(): ?Charter
    {
        return $this->charter;
    }

    public function setCharter(?Charter $charter): static
    {
        $this->charter = $charter;

        return $this;
    }

    public function getBlDate(bool $sqlFormat = false): ?\DateTimeImmutable
    {
        if (true === $sqlFormat) {
            return $this->blDate?->format("Y-m-d");
        } else {
            return $this->blDate;
        }
    }

    public function setBlDate(?\DateTimeImmutable $blDate): static
    {
        $this->blDate = $blDate;

        return $this;
    }

    public function getPol(): ?Port
    {
        return $this->pol;
    }

    public function setPol(?Port $pol): static
    {
        $this->pol = $pol;

        return $this;
    }

    public function getPod(): ?Port
    {
        return $this->pod;
    }

    public function setPod(?Port $pod): static
    {
        $this->pod = $pod;

        return $this;
    }

    public function getCommodity(): string
    {
        return $this->commodity;
    }

    public function setCommodity(string $commodity): static
    {
        $this->commodity = $commodity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;

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
        return [
            'id' => $this->getId(),
            'charter' => $this->charter?->getId(),
            'bl_date' => $this->blDate?->format('Y-m-d'),
            'pol' => $this->pol?->getLocode(),
            'pod' => $this->pod?->getLocode(),
            'marchandise' => $this->getCommodity(),
            'quantite' => $this->getQuantity(),
            'commentaire' => $this->getComments(),
        ];
    }
}
