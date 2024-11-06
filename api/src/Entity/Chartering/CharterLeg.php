<?php

// path: api/src/Entity/Chartering/CharterLeg.php

namespace App\Entity\Chartering;

use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\Port;
use App\Service\CharteringService;
use App\Service\PortService;

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

    /**
     * @param bool $sqlFormat 
     * 
     * @return \DateTimeImmutable|string|null 
     * 
     * @phpstan-return ($sqlFormat is false ? \DateTimeImmutable|null :string|null)
     */
    public function getBlDate(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->blDate?->format("Y-m-d");
        } else {
            return $this->blDate;
        }
    }

    public function setBlDate(\DateTimeImmutable|string|null $blDate): static
    {
        if (is_string($blDate)) {
            $this->blDate = new \DateTimeImmutable($blDate);
        } else {
            $this->blDate = $blDate;
        }

        return $this;
    }

    public function getPol(): ?Port
    {
        return $this->pol;
    }

    public function setPol(Port|string|null $pol): static
    {
        if (is_string($pol)) {
            $this->pol = (new PortService())->getPort($pol);
        } else {
            $this->pol = $pol;
        }

        return $this;
    }

    public function getPod(): ?Port
    {
        return $this->pod;
    }

    public function setPod(Port|string|null $pod): static
    {
        if (is_string($pod)) {
            $this->pod = (new PortService())->getPort($pod);
        } else {
            $this->pod = $pod;
        }

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
            'charter' => $this->getCharter()?->getId(),
            'bl_date' => $this->getBlDate()?->format('Y-m-d'),
            'pol' => $this->getPod()?->getLocode(),
            'pod' => $this->getPod()?->getLocode(),
            'marchandise' => $this->getCommodity(),
            'quantite' => $this->getQuantity(),
            'commentaire' => $this->getComments(),
        ];
    }
}
