<?php

// Path: api/src/Core/Traits/IdentifierTrait.php

declare(strict_types=1);

namespace App\Core\Traits;

trait IdentifierTrait
{
    public ?int $id = null {
        set => $value < 1 ? null : $value;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }
}
