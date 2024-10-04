<?php

// Path: api/src/Entity/Config/QuickAppointmentAdd.php

namespace App\Entity\Config;

use App\Core\Component\Module as Module;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;

abstract class QuickAppointmentAdd extends AbstractEntity
{
    use IdentifierTrait;

    protected ?Module $module;

    public function getModule(): Module
    {
        return $this->module;
    }
}
