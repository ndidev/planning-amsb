<?php

// Path: api/src/Core/HTTP/HTTPRequestQuery.php

namespace App\Core\HTTP;

use InvalidArgumentException;

/**
 * Class to handle request query parameters (after "?").
 * 
 * @phpstan-type QueryArray array<string|array<mixed>>
 */
final class HTTPRequestQuery implements \Stringable
{
    /**
     * @phpstan-param QueryArray $query
     */
    public function __construct(private array $query) {}

    /**
     * Get a query parameter.
     * 
     * @param string $name       Name of the query parameter.
     * @param mixed  $default    Default value if the query parameter is not set.
     * @param string $type       Type of the query parameter. Default is 'string'.
     * @param bool   $allowEmpty Allow an empty query parameter.
     * 
     * @return mixed Query parameter with the desired type.
     * 
     * @phpstan-return ($type is 'string'
     *                    ? string
     *                    : ($type is 'int'|'integer'
     *                        ? int
     *                        : ($type is 'float'|'double'
     *                             ? float
     *                             : ($type is 'bool'|'boolean'
     *                                  ? bool
     *                                  : ($type is 'datetime'
     *                                       ? \DateTime
     *                                       : mixed
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

        $paramValue = $this->query[$name] ?? $default;

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
     * Check if a query parameter is set.
     * 
     * @param string $name Name of the query parameter.
     * 
     * @return bool 
     */
    public function isSet(string $name): bool
    {
        return isset($this->query[$name]);
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
            'datetime' => 'datetime',
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
            if ('datetime' !== $type && gettype($default) !== $type) {
                throw new \InvalidArgumentException("Le type de la valeur par défaut n'est pas compatible avec le type attendu.");
            }

            // 'datetime' expects a valid \DateTime constructor string or a \DateTimeInterface object
            if ('datetime' === $type) {
                if (!is_string($default) && !($default instanceof \DateTimeInterface)) {
                    throw new \InvalidArgumentException("Le type de la valeur par défaut n'est pas compatible avec le type attendu.");
                }

                if (is_string($default)) {
                    try {
                        new \DateTime($default);
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
            'string' => is_scalar($paramValue) ? (string) $paramValue : $default,
            'integer' => is_scalar($paramValue) ? (int) $paramValue : $default,
            'double' => is_scalar($paramValue) ? (float) $paramValue : $default,
            'boolean' => is_scalar($paramValue) ? (bool) $paramValue : $default,
            'datetime' => (function () use ($paramValue, $default) {
                try {
                    if (is_string($paramValue)) {
                        return new \DateTime($paramValue);
                    }

                    if ($paramValue instanceof \DateTimeInterface) {
                        return \DateTime::createFromInterface($paramValue);
                    }
                } catch (\Exception) {
                    if (is_string($default)) {
                        return new \DateTime($default);
                    }

                    if ($default instanceof \DateTimeInterface) {
                        return \DateTime::createFromInterface($default);
                    }

                    throw new \InvalidArgumentException("La valeur par défaut n'est pas valide.");
                }
            })(),
            default => $paramValue,
        };
    }

    public function __toString(): string
    {
        return http_build_query($this->query);
    }
}
