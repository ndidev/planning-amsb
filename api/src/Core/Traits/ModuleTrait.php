<?php

// Path: api/src/Core/Traits/ModuleTrait.php

declare(strict_types=1);

namespace App\Core\Traits;

use App\Core\Component\Module;

trait ModuleTrait
{
    /**
     * @phpstan-var ?Module::* $module
     */
    public ?string $module = null {
        set(?string $value) => $this->module = Module::tryFrom($value);
    }
}
