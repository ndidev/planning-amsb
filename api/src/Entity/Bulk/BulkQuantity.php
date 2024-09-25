<?php

namespace App\Entity\Bulk;

class BulkQuantity
{
    private int $value = 0;
    private bool $max = false;

    public function __construct(int $value, bool $max)
    {
        $this->setValue($value);
        $this->setMax($max);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function isMax(): bool
    {
        return $this->max;
    }

    public function setMax(bool $max): static
    {
        $this->max = $max;

        return $this;
    }
}
