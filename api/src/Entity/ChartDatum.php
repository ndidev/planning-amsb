<?php

// Path: api/src/Entity/ChartDatum.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Validation\Constraints\PositiveNumber;

/**
 * @phpstan-type ChartDatumArray array{
 *                                 cote?: string,
 *                                 affichage?: string,
 *                                 valeur?: float,
 *                               }
 */
class ChartDatum extends AbstractEntity
{
    public string $name = '';

    public string $displayName = '';

    #[PositiveNumber('La valeur doit être un nombre positif.')]
    public float $value = 0.0;

    #[\Override]
    public function toArray(): array
    {
        return [
            'cote' => $this->name,
            'affichage' => $this->displayName,
            'valeur' => $this->value,
        ];
    }
}
