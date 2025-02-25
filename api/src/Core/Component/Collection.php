<?php

// Path: api/src/Core/Component/Collection.php

declare(strict_types=1);

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
        $this->items = \array_filter($this->items, fn($i) => $i !== $item);

        return $this;
    }

    /**
     * Clear the collection.
     */
    public function clear(): static
    {
        $this->items = [];

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
     * Apply a callback to every item of the collection.
     * 
     * @template U
     * @param null|callable(T): U $callback 
     * 
     * @return array<U>
     * @phpstan-return ($callback is null ? T[] : U[])
     */
    public function map(?callable $callback): array
    {
        return \array_map($callback, $this->items);
    }

    /**
     * Filter the collection using a callback.
     * 
     * @param null|callable(T): bool $callback     Function to filter the collection.
     * @param bool                   $preserveKeys Whether to preserve the keys of the collection.
     * 
     * @return self<T> 
     */
    public function filter(?callable $callback, bool $preserveKeys = false): self
    {
        return $preserveKeys
            ? new self(\array_filter($this->items, $callback))
            : new self(\array_values(\array_filter($this->items, $callback)));
    }

    /**
     * Check if the collection includes an item.
     * 
     * @param T|null $item Item to check.
     * 
     * @return bool 
     */
    public function includes(mixed $item): bool
    {
        return \in_array($item, $this->items, true);
    }

    /**
     * Apply a callback to every item of the collection.
     * 
     * @param callable(T): mixed $callback 
     * 
     * @return self<T> 
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $item) {
            $callback($item);
        }

        return $this;
    }

    /**
     * Transform the collection to an array.
     * 
     * Every item of the collection is also transformed to an array.
     * 
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return \array_map(function ($item) {
            return $item instanceof Arrayable ? $item->toArray() : $item;
        }, $this->items);
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
