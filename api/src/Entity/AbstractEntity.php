<?php

// Path: api/src/Entity/AbstractEntity.php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

abstract class AbstractEntity implements Arrayable, \JsonSerializable
{
    public function toArray(): array
    {
        $array = [];

        foreach ($this as $key => $value) {
            if ($value instanceof Arrayable) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
