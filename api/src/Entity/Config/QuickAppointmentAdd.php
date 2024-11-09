<?php

// Path: api/src/Entity/Config/QuickAppointmentAdd.php

declare(strict_types=1);

namespace App\Entity\Config;

use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;

abstract class QuickAppointmentAdd extends AbstractEntity
{
    use IdentifierTrait;

    protected ?string $module;

    public function getModule(): ?string
    {
        return $this->module;
    }
}
