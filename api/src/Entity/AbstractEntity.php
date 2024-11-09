<?php

// Path: api/src/Entity/AbstractEntity.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

abstract class AbstractEntity implements Arrayable, \JsonSerializable
{
    public function toArray(): array
    {
        $array = [];

        // @phpstan-ignore foreach.nonIterable
        foreach ($this as $key => $value) {
            if ($value instanceof Arrayable) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
