<?php

// Path: api/src/Entity/AbstractEntity.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Interfaces\Arrayable;
use App\Core\Validation\Validation;
use App\Core\Validation\ValidatorTrait;

abstract class AbstractEntity implements Arrayable, \JsonSerializable, Validation
{
    use ValidatorTrait;

    public function toArray(): array
    {
        $array = [];

        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $value = $property->getValue($this);

            if ($value instanceof Arrayable) {
                $array[$property->getName()] = $value->toArray();
            } else {
                $array[$property->getName()] = $value;
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

    public function __serialize(): array
    {
        return $this->toArray();
    }
}
