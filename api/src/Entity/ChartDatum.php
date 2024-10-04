<?php

// Path: api/src/Entity/ChartDatum.php

namespace App\Entity;

class ChartDatum extends AbstractEntity
{
    private string $name = '';
    private string $displayName = '';
    private float $value = 0;

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

    public function toArray(): array
    {
        return [
            'cote' => $this->getName(),
            'affichage' => $this->getDisplayName(),
            'valeur' => $this->getValue(),
        ];
    }
}
