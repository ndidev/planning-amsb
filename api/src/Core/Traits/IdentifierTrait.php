<?php

// Path: api/src/Core/Traits/IdentifierTrait.php

declare(strict_types=1);

namespace App\Core\Traits;

trait IdentifierTrait
{
    private ?int $id = null;

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
