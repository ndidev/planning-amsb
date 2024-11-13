<?php

// Path: api/tests/Entity/AbstractEntityTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Core\Interfaces\Arrayable;
use App\Entity\AbstractEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractEntity::class)]
final class AbstractEntityTest extends TestCase
{
    public function testToArray(): void
    {
        // Given
        $abstractEntity = $this->makeAbstractEntity();

        $expectedArray = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['key' => 'value']
        ];

        // When
        $actual = $abstractEntity->toArray();

        // Then
        $this->assertSame($expectedArray, $actual);
    }

    public function testJsonSerialize(): void
    {
        // Given
        $abstractEntity = $this->makeAbstractEntity();

        $expectedArray = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => ['key' => 'value']
        ];

        // When
        $actual = $abstractEntity->jsonSerialize();

        // Then
        $this->assertSame($expectedArray, $actual);
    }

    private function makeAbstractEntity(): AbstractEntity
    {
        $arrayableObject = new class implements Arrayable {
            public function toArray(): array
            {
                return ['key' => 'value'];
            }
        };

        $abstractEntity = new class($arrayableObject) extends AbstractEntity {
            public string $key1 = 'value1';
            public string $key2 = 'value2';
            public Arrayable $key3;

            public function __construct(Arrayable $key3)
            {
                $this->key3 = $key3;
            }
        };

        return $abstractEntity;
    }
}
