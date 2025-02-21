<?php

// Path: api/src/Entity/Chartering/CharterLeg.php

declare(strict_types=1);

namespace App\Entity\Chartering;

use App\Core\Validation\Constraints\Required;
use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\Port;

/**
 * @phpstan-type CharterLegArray array{
 *                                 id?: int,
 *                                 charter?: int,
 *                                 bl_date?: string,
 *                                 pol?: string,
 *                                 pod?: string,
 *                                 marchandise?: string,
 *                                 quantite?: string,
 *                                 commentaire?: string,
 *                               }
 */
class CharterLeg extends AbstractEntity
{
    use IdentifierTrait;

    private ?Charter $charter = null;

    private ?\DateTimeImmutable $blDate = null;

    #[Required("Le port de chargement est obligatoire.")]
    private ?Port $pol = null;

    #[Required("Le port de déchargement est obligatoire.")]
    private ?Port $pod = null;

    private string $commodity = '';

    private string $quantity = '';

    private string $comments = '';

    public function setCharter(?Charter $charter): static
    {
        $this->charter = $charter;

        return $this;
    }

    public function getCharter(): ?Charter
    {
        return $this->charter;
    }

    public function setBlDate(\DateTimeImmutable|string|null $blDate): static
    {
        $this->blDate = DateUtils::makeDateTimeImmutable($blDate);

        return $this;
    }

    public function getBlDate(): ?\DateTimeImmutable
    {
        return $this->blDate;
    }

    public function getSqlBlDate(): ?string
    {
        return $this->blDate?->format('Y-m-d');
    }

    public function setPol(?Port $pol): static
    {
        $this->pol = $pol;

        return $this;
    }

    public function getPol(): ?Port
    {
        return $this->pol;
    }

    public function setPod(?Port $pod): static
    {
        $this->pod = $pod;

        return $this;
    }

    public function getPod(): ?Port
    {
        return $this->pod;
    }

    public function setCommodity(string $commodity): static
    {
        $this->commodity = $commodity;

        return $this;
    }

    public function getCommodity(): string
    {
        return $this->commodity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
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

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'charter' => $this->getCharter()?->id,
            'bl_date' => $this->getBlDate()?->format('Y-m-d'),
            'pol' => $this->getPol()?->getLocode(),
            'pod' => $this->getPod()?->getLocode(),
            'marchandise' => $this->getCommodity(),
            'quantite' => $this->getQuantity(),
            'commentaire' => $this->getComments(),
        ];
    }
}
