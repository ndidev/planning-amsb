<?php

// Path: api/src/Entity/Port.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Array\ArrayHandler;
use App\Core\Validation\Constraints\Required;

/**
 * @phpstan-type PortArray array{
 *                           locode: string,
 *                           nom: string,
 *                           nom_affichage: string,
 *                         }
 */
class Port extends AbstractEntity
{
    #[Required("Le code LOCODE est obligatoire.")]
    public string $locode = '';

    #[Required("Le nom est obligatoire.")]
    public string $name = '';

    public string $displayName = '';

    /** 
     * @param ArrayHandler|PortArray|null $data
     */
    public function __construct(ArrayHandler|array|null $data = null)
    {
        if (null === $data) {
            return;
        }

        $dataAH = $data instanceof ArrayHandler ? $data : new ArrayHandler($data);

        $this->locode = $dataAH->getString("locode");
        $this->name = $dataAH->getString("nom");
        $this->displayName = $dataAH->getString("nom_affichage");
    }

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
            "locode" => $this->locode,
            "nom" => $this->name,
            "nom_affichage" => $this->displayName,
        ];
    }
}
