<?php

// Path: api/src/Entity/Country.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Validation\Constraints\Required;

class Country extends AbstractEntity
{
    #[Required("Le code ISO est obligatoire.")]
    private string $iso = '';

    #[Required("Le nom est obligatoire.")]
    private string $name = '';

    public function getISO(): string
    {
        return $this->iso;
    }

    public function setISO(string $iso): static
    {
        $this->iso = $iso;

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

    #[\Override]
    public function toArray(): array
    {
        return [
            "iso" => $this->getISO(),
            "nom" => $this->getName(),
        ];
    }
}
