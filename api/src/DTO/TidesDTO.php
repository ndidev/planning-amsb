<?php

// Path: api/src/DTO/TidesDTO.php

declare(strict_types=1);

namespace App\DTO;

/**
 * @phpstan-import-type TideArray from \App\Repository\TideRepository
 */
final class TidesDTO implements \JsonSerializable
{
    /**
     * @phpstan-var list<TideArray>
     */
    private array $tides = [];

    /**
     * @param list<array{
     *             date: string,
     *             heure: string,
     *             te_cesson: string|float,
     *             te_bassin: string|float
     *           }> $tides
     */
    public function __construct(array $tides)
    {
        for ($i = 0; $i < count($tides); $i++) {
            // @phpstan-ignore assign.propertyType
            $this->tides[$i] = [
                "date" => $tides[$i]["date"],
                "heure" => \substr($tides[$i]["heure"], 0, -3),
                "te_cesson" => (float) $tides[$i]["te_cesson"],
                "te_bassin" => (float) $tides[$i]["te_bassin"],
            ];
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->tides;
    }
}
