<?php

// Path: api/src/Core/Array/StaticArrayHandler.php

declare(strict_types=1);

namespace App\Core\Array;

/**
 * Abstract class for global variables.
 */
abstract class StaticArrayHandler
{
    abstract protected static function getInstance(): ArrayHandler;

    /**
     * Get the value of a variable.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getInstance()->get($key, $default);
    }

    /**
     * Get the value of a variable as a string.
     * 
     * @phpstan-return ($default is null ? string|null : string)
     * 
     * @throws \RuntimeException If the value cannot be cast to a string and `$throwException` is set to `true`.
     */
    public static function getString(
        string $key,
        ?string $default = '',
        bool $allowEmpty = true,
        bool $trim = true,
        bool $throwException = false,
    ): ?string {
        return static::getInstance()->getString($key, $default, $allowEmpty, $trim, $throwException);
    }

    /**
     * Get the value of a variable as an integer.
     * 
     * @phpstan-return ($default is null ? int|null : int)
     * 
     * @throws \RuntimeException If the value cannot be cast to an integer and `$throwException` is set to `true`.
     */
    public static function getInt(
        string $key,
        ?int $default = null,
        bool $throwException = false,
    ): ?int {
        return static::getInstance()->getInt($key, $default, $throwException);
    }

    /**
     * Get the value of a variable as a float.
     * 
     * @phpstan-return ($default is null ? float|null : float)
     * 
     * @throws \RuntimeException If the value cannot be cast to a float and `$throwException` is set to `true`.
     */
    public static function getFloat(
        string $key,
        float|int|null $default = null,
        bool $throwException = false,
    ): ?float {
        return static::getInstance()->getFloat($key, $default, $throwException);
    }

    /**
     * Get the value of a variable as a boolean.
     * 
     * @phpstan-return ($default is null ? bool|null : bool)
     * 
     * @throws \RuntimeException If the value cannot be cast to a boolean and `$throwException` is set to `true`.
     */
    public static function getBool(
        string $key,
        ?bool $default = false,
        bool $throwException = false,
    ): ?bool {
        return static::getInstance()->getBool($key, $default, $throwException);
    }

    /**
     * Get the value of a variable as an array.
     * 
     * @param string       $key
     * @param array<mixed> $default
     * 
     * @return ?array<mixed>
     * 
     * @phpstan-return ($default is null ? array<mixed>|null : array<mixed>)
     * 
     * @throws \RuntimeException If the value cannot be cast to an array and `$throwException` is set to `true`.
     */
    public static function getArray(
        string $key,
        ?array $default = [],
        bool $allowEmpty = true,
        bool $throwException = false,
    ): ?array {
        return static::getInstance()->getArray($key, $default, $allowEmpty, $throwException);
    }

    /**
     * Get the value of a variable as a `\DateTimeImmutable`.
     * 
     * @param string                         $key
     * @param \DateTimeInterface|string|null $default
     * 
     * @return ?\DateTimeImmutable
     * 
     * @phpstan-return ($default is null ? \DateTimeImmutable|null : \DateTimeImmutable)
     * 
     * @throws \RuntimeException If the value cannot be cast to a `\DateTimeImmutable` and `$throwException` is set to `true`.
     */
    public static function getDatetime(
        string $key,
        \DateTimeInterface|string|null $default = null,
        bool $allowEmpty = false,
        bool $throwException = false,
    ): ?\DateTimeImmutable {
        return static::getInstance()->getDatetime($key, $default, $allowEmpty, $throwException);
    }

    public static function isEmpty(): bool
    {
        return static::getInstance()->isEmpty();
    }

    /**
     * Check if a key is set.
     * 
     * @param string $key Name of the key.
     * 
     * @return bool 
     */
    public static function isSet(string $key): bool
    {
        return static::getInstance()->isSet($key);
    }

    /**
     * Set the value of a key.
     */
    public static function put(string $key, mixed $value): void
    {
        static::getInstance()->put($key, $value);
    }
}
