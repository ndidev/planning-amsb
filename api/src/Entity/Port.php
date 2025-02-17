<?php

// Path: api/src/Entity/Port.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Array\ArrayHandler;
use App\Core\Validation\Constraints\Required;

/**
 * @phpstan-type PortArray array{
 *                           locode: string,
 *                           nom?: string,
 *                           nom_affichage?: string,
 *                         }
 */
final class Port extends AbstractEntity
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
