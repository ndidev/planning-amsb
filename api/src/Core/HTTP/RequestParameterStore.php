<?php

// Path: api/src/Core/HTTP/RequestParameterStore.php

declare(strict_types=1);

namespace App\Core\HTTP;

/**
 * @phpstan-type QueryArray array<string|int, mixed|array<mixed>>
 */
abstract class RequestParameterStore
{
    /**
     * @phpstan-param QueryArray $parameterBag
     */
    protected function __construct(protected array $parameterBag) {}

    /**
     * Get a parameter.
     * 
     * @param string $name       Name of the parameter.
     * @param mixed  $default    Default value if the parameter is not set.
     * @param string $type       Type of the parameter. Default is 'string'.
     * @param bool   $allowEmpty Allow an empty parameter.
     * 
     * @return mixed Parameter with the desired type.
     * 
     * @phpstan-return ($type is 'string'
     *                    ? string
     *                    : ($type is 'int'|'integer'
     *                        ? int
     *                        : ($type is 'float'|'double'
     *                             ? float
     *                             : ($type is 'bool'|'boolean'
     *                                  ? bool
     *                                  : ($type is 'array'
     *                                       ? array<mixed>
     *                                       : ($type is 'datetime'
     *                                           ? \DateTimeImmutable
     *                                           : mixed
     *                                         )
     *                                    )
     *                               )
     *                          )
     *                      )
     *                 )
     */
    public function getParam(
        string $name,
        mixed $default = null,
        string $type = 'string',
        bool $allowEmpty = false
    ): mixed {
        $typeString = $this->getTypeString($type);

        $this->checkDefaultIsCompatibleWithType($default, $typeString);

        $paramValue = $this->parameterBag[$name] ?? $default;

        if (!$allowEmpty && empty($paramValue)) {
            $paramValue = $default;
        }

        // If null is expected as default, return null
        if ($paramValue === null) {
            return null;
        }

        return $this->convertParam($paramValue, $typeString, $default);
    }

    /**
     * Get a string parameter.
     * 
     * @param string $name 
     * @param null|string $default 
     * @param bool $allowEmpty 
     * 
     * @return null|string 
     * 
     * @phpstan-return ($default is null ? ($allowEmpty is false ? null|string : string) : string)
     * 
     * @throws \InvalidArgumentException 
     */
    public function getString(
        string $name,
        ?string $default = '',
        bool $allowEmpty = true
    ): ?string {
        return $this->getParam($name, $default, 'string', $allowEmpty);
    }

    /**
     * Get an integer parameter.
     * 
     * @param string $name 
     * @param null|int $default 
     * @param bool $allowEmpty 
     * 
     * @return null|int 
     * 
     * @phpstan-return ($default is null ? null|int : int)
     * 
     * @throws \InvalidArgumentException 
     */
    public function getInt(
        string $name,
        ?int $default = null,
        bool $allowEmpty = false
    ): ?int {
        return $this->getParam($name, $default, 'int', $allowEmpty);
    }

    /**
     * Get a float parameter.
     * 
     * @param string $name 
     * @param null|float $default 
     * @param bool $allowEmpty 
     * 
     * @return null|float 
     * 
     * @phpstan-return ($default is null ? null|float : float)
     * 
     * @throws \InvalidArgumentException 
     */
    public function getFloat(
        string $name,
        ?float $default = null,
        bool $allowEmpty = false
    ): ?float {
        return $this->getParam($name, $default, 'float', $allowEmpty);
    }

    /**
     * Get a boolean parameter.
     * 
     * @param string $name 
     * @param bool $default 
     * @param bool $allowEmpty 
     * 
     * @return ?bool 
     * 
     * @phpstan-return ($default is null ? null|bool : bool)
     * 
     * @throws \InvalidArgumentException 
     */
    public function getBool(
        string $name,
        ?bool $default = false,
        bool $allowEmpty = false
    ): ?bool {
        return $this->getParam($name, $default, 'bool', $allowEmpty);
    }

    /**
     * Get an array parameter.
     * 
     * @param string       $name 
     * @param array<mixed> $default 
     * @param bool         $allowEmpty 
     * 
     * @return array<mixed>
     * 
     * @throws \InvalidArgumentException
     */
    public function getArray(
        string $name,
        array $default = [],
        bool $allowEmpty = false
    ): array {
        return $this->getParam($name, $default, 'array', $allowEmpty);
    }

    /**
     * Get a datetime parameter.
     * 
     * @param string $name 
     * @param \DateTimeInterface|string|null $default 
     * @param bool $allowEmpty 
     * 
     * @return null|\DateTimeImmutable 
     * 
     * @phpstan-return ($default is null ? null|\DateTimeImmutable : \DateTimeImmutable)
     * 
     * @throws \InvalidArgumentException 
     */
    public function getDatetime(
        string $name,
        \DateTimeInterface|string|null $default = null,
        bool $allowEmpty = false
    ): ?\DateTimeImmutable {
        return $this->getParam($name, $default, 'datetime', $allowEmpty);
    }

    /**
     * Check if a query parameter is set.
     * 
     * @param string $name Name of the query parameter.
     * 
     * @return bool 
     */
    public function isSet(string $name): bool
    {
        return isset($this->parameterBag[$name]);
    }

    /**
     * Get the string representation of the type.
     * 
     * Can be used to compare with the `gettype` function.
     * 
     * @param string $type
     *  
     * @return string 
     * 
     * @throws \InvalidArgumentException 
     */
    private function getTypeString(string $type): string
    {
        return match ($type) {
            'string' => 'string',
            'int', 'integer' => 'integer',
            'float', 'double' => 'double',
            'bool', 'boolean' => 'boolean',
            'date', 'datetime' => 'datetime',
            'array' => 'array',
            default => throw new \InvalidArgumentException("Type '{$type}' is not supported."),
        };
    }

    /**
     * Check if the default value is compatible with the expected type.
     * 
     * @param mixed $default 
     * @param string $type 
     * 
     * @return void 
     * 
     * @throws \InvalidArgumentException 
     */
    private function checkDefaultIsCompatibleWithType(mixed $default, string $type): void
    {
        if ($default !== null) {
            if (\gettype($default) !== $type && 'datetime' !== $type) {
                throw new \InvalidArgumentException("Le type de la valeur par défaut n'est pas compatible avec le type attendu.");
            }

            // 'datetime' expects a valid \DateTimeImmutable constructor string or a \DateTimeInterface object
            if ('datetime' === $type) {
                if (!\is_string($default) && !($default instanceof \DateTimeInterface)) {
                    throw new \InvalidArgumentException("Le type de la valeur par défaut n'est pas compatible avec le type attendu.");
                }

                if (\is_string($default)) {
                    try {
                        new \DateTimeImmutable($default);
                    } catch (\Exception) {
                        throw new \InvalidArgumentException("La valeur par défaut n'est pas valide.");
                    }
                }
            }
        }
    }

    private function convertParam(mixed $paramValue, string $typeString, mixed $default): mixed
    {
        return match ($typeString) {
            'string' => \is_scalar($paramValue) ? (string) $paramValue : $default,
            'integer' => \is_scalar($paramValue) ? (int) $paramValue : $default,
            'double' => \is_scalar($paramValue) ? (float) $paramValue : $default,
            'boolean' => \is_scalar($paramValue) ? (bool) $paramValue : $default,
            'array' => \is_array($paramValue) ? $paramValue : $default,
            'datetime' => (function () use ($paramValue, $default) {
                try {
                    if (\is_string($paramValue)) {
                        return new \DateTimeImmutable($paramValue);
                    }

                    if ($paramValue instanceof \DateTimeInterface) {
                        return \DateTimeImmutable::createFromInterface($paramValue);
                    }
                } catch (\Exception) {
                    if (\is_string($default)) {
                        return new \DateTimeImmutable($default);
                    }

                    if ($default instanceof \DateTimeInterface) {
                        return \DateTimeImmutable::createFromInterface($default);
                    }

                    throw new \InvalidArgumentException("La valeur par défaut n'est pas valide.");
                }
            })(),
            default => $paramValue,
        };
    }
}
