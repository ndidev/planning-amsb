<?php

// Path: api/src/Entity/Port.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Validation\Constraints\Required;

class Port extends AbstractEntity
{
    #[Required("Le code LOCODE est obligatoire.")]
    private string $locode = '';

    #[Required("Le nom est obligatoire.")]
    private string $name = '';

    private string $displayName = '';

    public function getLocode(): string
    {
        return $this->locode;
    }

    public function setLocode(string $locode): static
    {
        $this->locode = $locode;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "locode" => $this->getLocode(),
            "nom" => $this->getName(),
            "nom_affichage" => $this->getDisplayName(),
        ];
    }
}
