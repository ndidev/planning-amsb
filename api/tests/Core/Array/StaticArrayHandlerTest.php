<?php

// Path: api/tests/Core/Array/AbstractArrayHandlerTest.php

declare(strict_types=1);

namespace App\Tests\Core\Globals;

use App\Core\Array\ArrayHandler;
use App\Core\Array\StaticArrayHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StaticArrayHandler::class)]
#[UsesClass(ArrayHandler::class)]
final class StaticArrayHandlerTest extends TestCase
{
    /**
     * @param array<mixed> $store 
     * @param mixed $default 
     * @param mixed $expected 
     */
    #[DataProvider('generateMixedValues')]
    public function testGet(
        array $store,
        mixed $default,
        mixed $expected
    ): void {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $value = $arrayHandler::get('key', $default);

        // Then
        $this->assertSame($expected, $value);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateMixedValues(): \Generator
    {
        // Data structure: [title, store, default, expected]
        $cases = [
            [
                "title" => "set value is returned",
                "store" => ["key" => "value"],
                "default" => "",
                "expected" => "value",
            ],
            [
                "title" => "default value is returned when key is not set",
                "store" => [],
                "default" => "value",
                "expected" => "value",
            ],
            [
                "title" => "null is returned when key is not set and default is null",
                "store" => [],
                "default" => null,
                "expected" => null,
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
                $case["default"],
                $case["expected"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     * @param ?string $default 
     * @param bool $allowEmpty 
     * @param ?string $expected 
     */
    #[DataProvider('generateValidStringValues')]
    public function testGetString(
        array $store,
        ?string $default,
        bool $allowEmpty,
        ?string $expected
    ): void {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $value = $arrayHandler::getString('key', $default, $allowEmpty, throwException: false);

        // Then
        $this->assertSame($expected, $value);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateValidStringValues(): \Generator
    {
        // Data structure: [title, store, default, allowEmpty, expected]
        $cases = [
            [
                "title" => "normal string is returned",
                "store" => ["key" => "value"],
                "default" => "",
                "allowEmpty" => true,
                "expected" => "value",
            ],
            [
                "title" => "integer is cast to string",
                "store" => ["key" => 2],
                "default" => "",
                "allowEmpty" => true,
                "expected" => "2",
            ],
            [
                "title" => "empty string is returned when allowed",
                "store" => ["key" => ""],
                "default" => "",
                "allowEmpty" => true,
                "expected" => "",
            ],
            [
                "title" => "default value is returned when empty string is not allowed",
                "store" => ["key" => ""],
                "default" => "value",
                "allowEmpty" => false,
                "expected" => "value",
            ],
            [
                "title" => "default value is returned when key is not set",
                "store" => [],
                "default" => "value",
                "allowEmpty" => true,
                "expected" => "value",
            ],
            [
                "title" => "null is returned when key is not set and default is null",
                "store" => [],
                "default" => null,
                "allowEmpty" => true,
                "expected" => null,
            ],
            [
                "title" => "invalid value falls back to default",
                "store" => ["key" => []],
                "default" => "value",
                "allowEmpty" => true,
                "expected" => "value",
            ]
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
                $case["default"],
                $case["allowEmpty"],
                $case["expected"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     */
    #[DataProvider('generateInvalidStringValues')]
    public function testGetStringThrowsException(array $store): void
    {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $arrayHandler::getString('key', throwException: true);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateInvalidStringValues(): \Generator
    {
        // Data structure: [title, store]
        $cases = [
            [
                "title" => "array is not allowed",
                "store" => ["key" => []],
            ],
            [
                "title" => "object is not allowed",
                "store" => ["key" => new \stdClass()],
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     * @param ?int $default 
     * @param ?int $expected 
     */
    #[DataProvider('generateValidIntegerValues')]
    public function testGetInt(
        array $store,
        ?int $default,
        ?int $expected
    ): void {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $value = $arrayHandler::getInt('key', $default, throwException: false);

        // Then
        $this->assertSame($expected, $value);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateValidIntegerValues(): \Generator
    {
        // Data structure: [title, store, default, expected]
        $cases = [
            [
                "title" => "normal integer is returned",
                "store" => ["key" => 1],
                "default" => null,
                "expected" => 1,
            ],
            [
                "title" => "string integer is cast to integer",
                "store" => ["key" => "1"],
                "default" => null,
                "expected" => 1,
            ],
            [
                "title" => "default value is returned when key is not set",
                "store" => [],
                "default" => 3,
                "expected" => 3,
            ],
            [
                "title" => "null is returned when key is not set and default is null",
                "store" => [],
                "default" => null,
                "expected" => null,
            ],
            [
                "title" => "invalid value falls back to default",
                "store" => ["key" => []],
                "default" => 4,
                "expected" => 4,
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
                $case["default"],
                $case["expected"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     */
    #[DataProvider('generateInvalidIntegerValues')]
    public function testGetIntThrowsException(array $store): void
    {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $arrayHandler::getInt('key', throwException: true);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateInvalidIntegerValues(): \Generator
    {
        // Data structure: [title, store]
        $cases = [
            [
                "title" => "non-numeric string is not allowed",
                "store" => ["key" => "value"],
            ],
            [
                "title" => "array is not allowed",
                "store" => ["key" => []],
            ],
            [
                "title" => "object is not allowed",
                "store" => ["key" => new \stdClass()],
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     * @param ?float $default 
     * @param ?float $expected 
     */
    #[DataProvider('generateValidFloatValues')]
    public function testGetFloat(
        array $store,
        ?float $default,
        ?float $expected
    ): void {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $value = $arrayHandler::getFloat('key', $default, throwException: false);

        // Then
        $this->assertSame($expected, $value);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateValidFloatValues(): \Generator
    {
        // Data structure: [title, store, default, expected]
        $cases = [
            [
                "title" => "normal float is returned",
                "store" => ["key" => 1.1],
                "default" => null,
                "expected" => 1.1,
            ],
            [
                "title" => "string float is cast to float",
                "store" => ["key" => "1.1"],
                "default" => null,
                "expected" => 1.1,
            ],
            [
                "title" => "integer is cast to float",
                "store" => ["key" => 1],
                "default" => null,
                "expected" => 1.0,
            ],
            [
                "title" => "default value is returned when key is not set",
                "store" => [],
                "default" => 3.3,
                "expected" => 3.3,
            ],
            [
                "title" => "null is returned when key is not set and default is null",
                "store" => [],
                "default" => null,
                "expected" => null,
            ],
            [
                "title" => "invalid value falls back to default",
                "store" => ["key" => []],
                "default" => 4.4,
                "expected" => 4.4,
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
                $case["default"],
                $case["expected"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     */
    #[DataProvider('generateInvalidFloatValues')]
    public function testGetFloatThrowsException(array $store): void
    {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $arrayHandler::getFloat('key', throwException: true);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateInvalidFloatValues(): \Generator
    {
        // Data structure: [title, store]
        $cases = [
            [
                "title" => "non-numeric string is not allowed",
                "store" => ["key" => "value"],
            ],
            [
                "title" => "array is not allowed",
                "store" => ["key" => []],
            ],
            [
                "title" => "object is not allowed",
                "store" => ["key" => new \stdClass()],
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     * @param ?bool $default 
     * @param ?bool $expected 
     */
    #[DataProvider('generateValidBoolValues')]
    public function testGetBool(
        array $store,
        ?bool $default,
        ?bool $expected
    ): void {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $value = $arrayHandler::getBool('key', $default, throwException: false);

        // Then
        $this->assertSame($expected, $value);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateValidBoolValues(): \Generator
    {
        // Data structure: [title, store, default, expected]
        $cases = [
            [
                "title" => "true is returned",
                "store" => ["key" => true],
                "default" => null,
                "expected" => true,
            ],
            [
                "title" => "false is returned",
                "store" => ["key" => false],
                "default" => null,
                "expected" => false,
            ],
            [
                "title" => "true is returned when key is not set and default is true",
                "store" => [],
                "default" => true,
                "expected" => true,
            ],
            [
                "title" => "false is returned when key is not set and default is false",
                "store" => [],
                "default" => false,
                "expected" => false,
            ],
            [
                "title" => "null is returned when key is not set and default is null",
                "store" => [],
                "default" => null,
                "expected" => null,
            ],
            [
                "title" => "'true' string is cast to true",
                "store" => ["key" => 'true'],
                "default" => null,
                "expected" => true,
            ],
            [
                "title" => "'false' string is cast to false",
                "store" => ["key" => 'false'],
                "default" => null,
                "expected" => false,
            ],
            [
                "title" => "1 is cast to true",
                "store" => ["key" => 1],
                "default" => null,
                "expected" => true,
            ],
            [
                "title" => "0 is cast to false",
                "store" => ["key" => 0],
                "default" => null,
                "expected" => false,
            ],
            [
                "title" => "invalid value falls back to default",
                "store" => ["key" => []],
                "default" => true,
                "expected" => true,
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
                $case["default"],
                $case["expected"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     */
    #[DataProvider('generateInvalidBoolValues')]
    public function testGetBoolThrowsException(array $store): void
    {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $arrayHandler::getBool('key', throwException: true);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateInvalidBoolValues(): \Generator
    {
        // Data structure: [title, store]
        $cases = [
            [
                "title" => "array is not allowed",
                "store" => ["key" => []],
            ],
            [
                "title" => "object is not allowed",
                "store" => ["key" => new \stdClass()],
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     * @param ?array<mixed> $default 
     * @param bool $allowEmpty 
     * @param ?array<mixed> $expected 
     */
    #[DataProvider('generateValidArrayValues')]
    public function testGetArray(
        array $store,
        ?array $default,
        bool $allowEmpty,
        ?array $expected
    ): void {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $value = $arrayHandler::getArray('key', $default, $allowEmpty, throwException: false);

        // Then
        $this->assertSame($expected, $value);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateValidArrayValues(): \Generator
    {
        // Data structure: [title, store, default, allowEmpty, expected]
        $cases = [
            [
                "title" => "normal array is returned",
                "store" => ["key" => ['value']],
                "default" => null,
                "allowEmpty" => true,
                "expected" => ['value'],
            ],
            [
                "title" => "empty array is returned when allowed",
                "store" => ["key" => []],
                "default" => ['value'],
                "allowEmpty" => true,
                "expected" => [],
            ],
            [
                "title" => "default value is returned when empty array is not allowed",
                "store" => ["key" => []],
                "default" => ['value'],
                "allowEmpty" => false,
                "expected" => ['value'],
            ],
            [
                "title" => "default value is returned when key is not set",
                "store" => [],
                "default" => ['value'],
                "allowEmpty" => true,
                "expected" => ['value'],
            ],
            [
                "title" => "null is returned when key is not set and default is null",
                "store" => [],
                "default" => null,
                "allowEmpty" => true,
                "expected" => null,
            ],
            [
                "title" => "invalid value falls back to default",
                "store" => ["key" => 'value'],
                "default" => ['value'],
                "allowEmpty" => true,
                "expected" => ['value'],
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
                $case["default"],
                $case["allowEmpty"],
                $case["expected"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     */
    #[DataProvider('generateInvalidArrayValues')]
    public function testGetArrayThrowsException(array $store): void
    {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $arrayHandler::getArray('key', throwException: true);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateInvalidArrayValues(): \Generator
    {
        // Data structure: [title, store]
        $cases = [
            [
                "title" => "string is not allowed",
                "store" => ["key" => "value"],
            ],
            [
                "title" => "object is not allowed",
                "store" => ["key" => new \stdClass()],
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     * @param \DateTimeImmutable|string|null $default 
     * @param bool $allowEmpty 
     * @param ?\DateTimeImmutable $expected 
     */
    #[DataProvider('generateValidDatetimeValues')]
    public function testGetDatetime(
        array $store,
        \DateTimeImmutable|string|null $default,
        bool $allowEmpty,
        ?\DateTimeImmutable $expected
    ): void {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $value = $arrayHandler::getDatetime('key', $default, $allowEmpty, throwException: false);

        // Then
        $this->assertEqualsWithDelta($expected, $value, DATETIME_ALLOWED_DELTA);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateValidDatetimeValues(): \Generator
    {
        // Data structure: [title, store, default, allowEmpty, expected]
        $cases = [
            [
                "title" => "datetime is returned from valid string",
                "store" => ["key" => "2021-01-01"],
                "default" => null,
                "allowEmpty" => true,
                "expected" => new \DateTimeImmutable('2021-01-01'),
            ],
            [
                "title" => "datetime is returned from object",
                "store" => ["key" => new \DateTimeImmutable('2022-02-02')],
                "default" => null,
                "allowEmpty" => true,
                "expected" => new \DateTimeImmutable('2022-02-02'),
            ],
            [
                "title" => "default value from object is returned when key is not set",
                "store" => [],
                "default" => new \DateTimeImmutable('2023-03-03'),
                "allowEmpty" => true,
                "expected" => new \DateTimeImmutable('2023-03-03'),
            ],
            [
                "title" => "default value from string is returned when key is not set",
                "store" => [],
                "default" => '2024-04-04',
                "allowEmpty" => true,
                "expected" => new \DateTimeImmutable('2024-04-04'),
            ],
            [
                "title" => "null is returned when key is not set and default is null",
                "store" => [],
                "default" => null,
                "allowEmpty" => true,
                "expected" => null,
            ],
            [
                "title" => "invalid value falls back to default",
                "store" => ["key" => []],
                "default" => new \DateTimeImmutable('2025-05-05'),
                "allowEmpty" => true,
                "expected" => new \DateTimeImmutable('2025-05-05'),
            ],
            [
                "title" => "invalid string falls back to default",
                "store" => ["key" => 'invalid'],
                "default" => new \DateTimeImmutable('2026-06-06'),
                "allowEmpty" => true,
                "expected" => new \DateTimeImmutable('2026-06-06'),
            ],
            [
                "title" => "empty string falls back to default when empty is not allowed",
                "store" => ["key" => ''],
                "default" => new \DateTimeImmutable('2027-07-07'),
                "allowEmpty" => false,
                "expected" => new \DateTimeImmutable('2027-07-07'),
            ],
            [
                "title" => "empty string returns current datetime when empty is allowed",
                "store" => ["key" => ''],
                "default" => null,
                "allowEmpty" => true,
                "expected" => new \DateTimeImmutable(),
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
                $case["default"],
                $case["allowEmpty"],
                $case["expected"],
            ];
        }
    }

    /**
     * @param array<mixed> $store 
     */
    #[DataProvider('generateInvalidDatetimeValues')]
    public function testGetDatetimeThrowsException(array $store): void
    {
        // Given
        $arrayHandler = $this->makeStaticArrayHandler($store);

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $arrayHandler::getDatetime('key', throwException: true);
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public static function generateInvalidDatetimeValues(): \Generator
    {
        // Data structure: [title, store]
        $cases = [
            [
                "title" => "invalid datetime string",
                "store" => ["key" => "invalid"],
            ],
            [
                "title" => "array is not allowed",
                "store" => ["key" => []],
            ],
            [
                "title" => "object is not allowed",
                "store" => ["key" => new \stdClass()],
            ],
        ];

        foreach ($cases as $case) {
            yield $case["title"] => [
                $case["store"],
            ];
        }
    }

    public function testIsNotEmpty(): void
    {
        // Given
        $store = [
            'key1' => 'value1',
        ];

        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $isEmpty = $arrayHandler::isEmpty();

        // Then
        $this->assertFalse($isEmpty);
    }

    public function testIsEmpty(): void
    {
        // Given
        $store = [];

        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $isEmpty = $arrayHandler::isEmpty();

        // Then
        $this->assertTrue($isEmpty);
    }

    public function testIsSet(): void
    {
        // Given
        $store = [
            'key1' => 'value1',
        ];

        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $isSet1 = $arrayHandler::isSet('key1');
        $isSet2 = $arrayHandler::isSet('key2');

        // Then
        $this->assertTrue($isSet1);
        $this->assertFalse($isSet2);
    }

    public function testPut(): void
    {
        // Given
        $store = [
            'key1' => 'value1',
        ];

        $arrayHandler = $this->makeStaticArrayHandler($store);

        // When
        $arrayHandler::put('key2', 'value2');
        $value2 = $arrayHandler::get('key2');

        // Then
        $this->assertSame('value2', $value2);
    }

    /**
     * @param array<mixed> $store 
     */
    private function makeStaticArrayHandler(array $store): StaticArrayHandler
    {
        return new class($store) extends StaticArrayHandler {
            protected static ArrayHandler $instance;

            /** @param array<mixed> $store */
            public function __construct(array $store)
            {
                static::$instance = new ArrayHandler($store);
            }

            protected static function getInstance(): ArrayHandler
            {
                return static::$instance;
            }
        };
    }
}
