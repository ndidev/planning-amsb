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
    private string $name = '';

    private string $displayName = '';

    #[PositiveNumber('La valeur doit être un nombre positif.')]
    private float $value = 0.0;

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

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;

        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'cote' => $this->getName(),
            'affichage' => $this->getDisplayName(),
            'valeur' => $this->getValue(),
        ];
    }
}
