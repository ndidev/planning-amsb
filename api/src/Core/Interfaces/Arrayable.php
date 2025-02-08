<?php

// Path: api/src/Core/Interfaces/Arrayable.php

declare(strict_types=1);

namespace App\Core\Interfaces;

/**
 * This interface defines a toArray() method that converts an object into an array.
 */
interface Arrayable
{
    /**
     * Converts the object into an array.
     * 
     * @return array<mixed> The array representing the object.
     */
    public function toArray(): array;
}
