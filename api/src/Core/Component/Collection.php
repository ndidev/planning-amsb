<?php

// Path: api/src/Core/Component/Collection.php

namespace App\Core\Component;

use App\Core\Interfaces\Arrayable;

/**
 * @template T
 * 
 * @implements \IteratorAggregate<T>
 */
class Collection implements \IteratorAggregate, \Countable, Arrayable, \JsonSerializable
{
    /**
     * @param T[] $items
     */
    public function __construct(private array $items = []) {}

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

    /**
     * Remove an item from the collection.
     * 
     * @param T $item 
     */
    public function remove(mixed $item): static
    {
        $this->items = array_filter($this->items, fn($i) => $i !== $item);

        return $this;
    }

    /**
     * @return \ArrayIterator<int, T>
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
     * Every item of the collection preserves its type.
     * 
     * @return T[]
     */
    public function asArray(): array
    {
        return $this->items;
    }

    /**
     * Transform the collection to an array.
     * 
     * Every item of the collection is also transformed to an array.
     * 
     * @return array[]
     */
    public function toArray(): array
    {
        return array_map(fn($item) => $item->toArray(), $this->items);
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
