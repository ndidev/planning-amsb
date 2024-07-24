<?php

// Path: api/src/Entity/AbstractEntity.php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

/**
 * This class is the base class for all entities.
 */
abstract class AbstractEntity implements Arrayable, \JsonSerializable
{
    /**
     * Converts the object to an associative array.
     * 
     * @return array<string, mixed> The associative array representing the object.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
