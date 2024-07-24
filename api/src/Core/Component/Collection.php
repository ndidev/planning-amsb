<?php

// Path: api/src/Core/Component/Collection.php

namespace App\Core\Component;

use App\Core\Interfaces\Arrayable;

/**
 * @template T
 */
class Collection implements \IteratorAggregate, \Countable, Arrayable, \JsonSerializable
{
    /**
     * @param T[] $items
     */
    public function __construct(private array $items = [])
    {
    }

    /**
     * Add an item to the collection.
     * 
     * @param T $item 
     */
    public function add(mixed $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    // public function remove(Identifiable $item): static
    // {
    //     $this->items = array_filter($this->items, fn($i) => $i->getId() !== $item->getId());

    //     return $this;
    // }

    /**
     * @return T[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Transform the collection to an array.
     * 
     * @return T[]
     */
    public function toArray(): array
    {
        return array_map(fn ($item) => $item->toArray(), $this->items);
    }

    /**
     * Transform the collection to an array so that it can be serialized to JSON.
     * 
     * @return T[]
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
