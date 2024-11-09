<?php

// Path: api/src/DTO/SupplierWithUniqueDeliveryNoteNumber.php

declare(strict_types=1);

namespace App\DTO;

class SupplierWithUniqueDeliveryNoteNumber
{
    private ?int $id = null;
    private string $regexp = '';

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setRegexp(string $regexp): static
    {
        $this->regexp = $regexp;

        return $this;
    }

    public function getRegexp(): string
    {
        return $this->regexp;
    }
}
