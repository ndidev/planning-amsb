<?php

// Path: api/tests/Core/Component/CollectionTest.php

declare(strict_types=1);

namespace App\Tests\Core\Component;

use App\Core\Component\Collection;
use App\Core\Interfaces\Arrayable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collection::class)]
final class CollectionTest extends TestCase
{
    public function testZeroItemOnEmptyInstanciation(): void
    {
        // Given
        $collection = new Collection();

        // Then
        $this->assertCount(0, $collection);
    }

    public function  testItemsAreSetOnInstanciation(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // Then
        $this->assertCount(3, $collection);
    }

    public function testAddItemToCollection(): void
    {
        // Given
        $collection = new Collection();

        // When
        $collection->add('item');

        // Then
        $this->assertCount(1, $collection);
    }

    public function testRemoveItemFromCollection(): void
    {
        // Given
        $collection = new Collection();
        $collection->add('item');

        // When
        $collection->remove('item');

        // Then
        $this->assertCount(0, $collection);
    }

    public function testClear(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $collection->clear();

        // Then
        $this->assertCount(0, $collection);
    }

    public function testGetArrayIterator(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $arrayIterator = $collection->getIterator();

        // Then
        $this->assertInstanceOf(\ArrayIterator::class, $arrayIterator);
    }

    public function testAsArrayReturnsAnArray(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $array = $collection->asArray();

        // Then
        $this->assertContains('item1', $array);
        $this->assertContains('item2', $array);
        $this->assertContains('item3', $array);
    }

    public function testToArrayReturnsNonArrayableItemsUntouched(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $array = $collection->asArray();

        // Then
        $this->assertSame(['item1', 'item2', 'item3'], $array);
    }

    public function testToArrayReturnsArrayableItemsUsingToarrayMethod(): void
    {
        // Given
        $arrayableObject1 = new TestArrayableClass(['item1', 'item2', 'item3']);
        $arrayableObject2 = new TestArrayableClass(['item4', 'item5', 'item6']);

        $collection = new Collection([$arrayableObject1, $arrayableObject2]);

        $expected = [
            ['ITEM1', 'ITEM2', 'ITEM3'],
            ['ITEM4', 'ITEM5', 'ITEM6'],
        ];

        // When
        $array = $collection->toArray();

        // Then
        $this->assertSame($expected, $array);
    }

    public function testMapWithCallable(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $mapped = $collection->map(fn($item) => \strtoupper($item));

        // Then
        $this->assertSame(['ITEM1', 'ITEM2', 'ITEM3'], $mapped);
    }

    public function testMapWithFunctionName(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $mapped = $collection->map('strtoupper');

        // Then
        $this->assertSame(['ITEM1', 'ITEM2', 'ITEM3'], $mapped);
    }

    public function testMapWithNullCallable(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $mapped = $collection->map(null);

        // Then
        $this->assertSame(['item1', 'item2', 'item3'], $mapped);
    }

    public function testFilter(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $filtered = $collection->filter(fn($item) => $item !== 'item2');

        // Then
        $this->assertEquals(new Collection(['item1', 'item3']), $filtered);
    }

    public function testFilterWithNullCallable(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $filtered = $collection->filter(null);

        // Then
        $this->assertEquals(new Collection(['item1', 'item2', 'item3']), $filtered);
    }

    public function testFilterWithPreserveKeys(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $filtered = $collection->filter(fn($item) => $item !== 'item2', true);

        // Then
        $this->assertEquals(new Collection([0 => 'item1', 2 => 'item3']), $filtered);
    }

    public function testIncludes(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // Then
        $this->assertTrue($collection->includes('item2'));
        $this->assertFalse($collection->includes('item4'));
    }

    public function testEach(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);
        $items = ['test'];

        // When
        $collection->each(function ($item) use (&$items) {
            \array_push($items, \strtoupper($item));
        });

        // Then
        $this->assertSame(['test', 'ITEM1', 'ITEM2', 'ITEM3'], $items);
    }

    public function testJsonSerialize(): void
    {
        // Given
        $collection = new Collection(['item1', 'item2', 'item3']);

        // When
        $json = \json_encode($collection);

        // Then
        $this->assertSame('["item1","item2","item3"]', $json);
    }
}

final class TestArrayableClass implements Arrayable
{
    /**
     * @param string[] $items
     */
    public function __construct(private array $items) {}

    public function toArray(): array
    {
        return \array_map('strtoupper', $this->items);
    }
}
