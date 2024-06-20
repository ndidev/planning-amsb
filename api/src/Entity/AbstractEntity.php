<?php

// Path: api/src/Entity/AbstractEntity.php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

abstract class AbstractEntity implements Arrayable, \JsonSerializable
{
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
