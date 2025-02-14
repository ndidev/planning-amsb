<?php

// Path: api/src/Entity/Country.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Array\ArrayHandler;
use App\Core\Validation\Constraints\Required;

/**
 * @phpstan-type CountryArray array{
 *                              iso: string,
 *                              nom?: string,
 *                            }
 */
final class Country extends AbstractEntity
{
    #[Required("Le code ISO est obligatoire.")]
    public string $iso = '';

    #[Required("Le nom est obligatoire.")]
    public string $name = '';

    /**
     * @param ArrayHandler|CountryArray|null $data 
     */
    public function __construct(ArrayHandler|array|null $data = null)
    {
        if (null === $data) {
            return;
        }

        $dataAH = $data instanceof ArrayHandler ? $data : new ArrayHandler($data);

        $this->iso = $dataAH->getString('iso');
        $this->name = $dataAH->getString('nom');
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "iso" => $this->iso,
            "nom" => $this->name,
        ];
    }
}
