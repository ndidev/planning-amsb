<?php

// Path: api/src/Core/Array/ArrayHandler.php

declare(strict_types=1);

namespace App\Core\Array;

use App\Core\Component\DateUtils;

/**
 * Array management.
 */
class ArrayHandler
{
    /**
     * @param array<mixed> $store 
     */
    public function __construct(protected array &$store) {}

    /**
     * Get the value of a variable.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->store[$key] ?? $default;

        return $value;
    }

    /**
     * Get the value of a variable as a string.
     * 
     * @phpstan-return ($default is null ? string|null : string)
     * 
     * @throws \RuntimeException If the value cannot be cast to a string and `$throwException` is set to `true`.
     */
    public function getString(
        string $key,
        ?string $default = '',
        bool $allowEmpty = true,
        bool $throwException = false,
    ): ?string {
        $value = $this->get($key, $default);

        if ('' === $value && !$allowEmpty) {
            $value = $default;
        }

        if (null === $value) {
            return null;
        }

        if (\is_scalar($value)) {
            return (string) $value;
        }

        if ($throwException) {
            throw new \RuntimeException("The value of the variable '$key' must be a string.");
        }

        return $default;
    }

    /**
     * Get the value of a variable as an integer.
     * 
     * @phpstan-return ($default is null ? int|null : int)
     * 
     * @throws \RuntimeException If the value cannot be cast to an integer and `$throwException` is set to `true`.
     */
    public function getInt(
        string $key,
        ?int $default = null,
        bool $throwException = false,
    ): ?int {
        $value = $this->get($key, $default);

        if (null === $value) {
            return null;
        }

        if (\is_numeric($value)) {
            return (int) $value;
        }

        if ($throwException) {
            throw new \RuntimeException("The value of the variable '$key' must be a number.");
        }

        return $default;
    }

    /**
     * Get the value of a variable as a float.
     * 
     * @phpstan-return ($default is null ? float|null : float)
     * 
     * @throws \RuntimeException If the value cannot be cast to a float and `$throwException` is set to `true`.
     */
    public function getFloat(
        string $key,
        float|int|null $default = null,
        bool $throwException = false,
    ): ?float {
        $value = $this->get($key, $default);

        if (null === $value) {
            return null;
        }

        if (\is_numeric($value)) {
            return (float) $value;
        }

        if ($throwException) {
            throw new \RuntimeException("The value of the variable '$key' must be a number.");
        }

        return $default;
    }

    /**
     * Get the value of a variable as a boolean.
     * 
     * @phpstan-return ($default is null ? bool|null : bool)
     * 
     * @throws \RuntimeException If the value cannot be cast to a boolean and `$throwException` is set to `true`.
     */
    public function getBool(
        string $key,
        ?bool $default = false,
        bool $throwException = false,
    ): ?bool {
        $value = $this->get($key, $default);

        if (null === $value) {
            return null;
        }

        if ('true' === $value) {
            return true;
        }

        if ('false' === $value) {
            return false;
        }

        if (\is_scalar($value)) {
            return (bool) $value;
        }

        if ($throwException) {
            throw new \RuntimeException("The value of the variable '$key' must be a boolean.");
        }

        return $default;
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
    public function getArray(
        string $key,
        ?array $default = [],
        bool $allowEmpty = true,
        bool $throwException = false,
    ): ?array {
        $value = $this->get($key, $default);

        if (empty($value) && !$allowEmpty) {
            $value = $default;
        }

        if (null === $value) {
            return null;
        }

        if (\is_array($value)) {
            return $value;
        }

        if ($throwException) {
            throw new \RuntimeException("The value of the variable '$key' must be an array.");
        }

        return $default;
    }

    /**
     * Get the value of a variable as a `\DateTimeImmutable`.
     * 
     * @param string                         $key            Name of the key.
     * @param \DateTimeInterface|string|null $default        Default value.
     * @param bool                           $allowEmpty     Allow empty value (i.e.: current datetime).
     * @param bool                           $throwException If `true`, throw an exception if the value cannot be cast to a `\DateTimeImmutable`.
     *                                                       If `false`, return the default value.
     * 
     * @return ?\DateTimeImmutable
     * 
     * @phpstan-return ($default is null ? \DateTimeImmutable|null : \DateTimeImmutable)
     * 
     * @throws \RuntimeException If the value cannot be cast to a `\DateTimeImmutable` and `$throwException` is set to `true`.
     */
    public function getDatetime(
        string $key,
        \DateTimeInterface|string|null $default = null,
        bool $allowEmpty = false,
        bool $throwException = false,
    ): ?\DateTimeImmutable {
        $value = $this->get($key, $default);

        if ('' === $value && !$allowEmpty) {
            $value = $default;
        }

        if (null === $value) {
            return null;
        }

        if (\is_string($value) || $value instanceof \DateTimeInterface) {
            $datetime = DateUtils::makeDateTimeImmutable($value);

            if (null === $datetime) {
                if ($throwException) {
                    throw new \RuntimeException("The value of the variable '$key' must be a datetime or a valid datetime string.");
                } else {
                    $datetime = DateUtils::makeDateTimeImmutable($default);
                }
            }

            return $datetime;
        }

        if ($throwException) {
            throw new \RuntimeException("The value of the variable '$key' must be a datetime or a valid datetime string.");
        }

        return DateUtils::makeDateTimeImmutable($default);
    }

    public function isEmpty(): bool
    {
        return empty($this->store);
    }

    /**
     * Check if a key is set.
     * 
     * @param string $key Name of the key.
     * 
     * @return bool 
     */
    public function isSet(string $key): bool
    {
        return isset($this->store[$key]);
    }

    /**
     * Set the value of a key.
     */
    public function put(string $key, mixed $value): void
    {
        $this->store[$key] = $value;
    }
}
