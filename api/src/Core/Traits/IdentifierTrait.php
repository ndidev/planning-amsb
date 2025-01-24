<?php

// Path: api/src/Core/Traits/IdentifierTrait.php

declare(strict_types=1);

namespace App\Core\Traits;

trait IdentifierTrait
{
    public ?int $id = null {
        set(?int $id) {
            $this->id = $id < 1 ? null : $id;
        }
    }

    public function setId(?int $id): static
    {
        $this->id = $id < 1 ? null : $id;

        return $this;
    }
}
