<?php

// Path: api/src/DTO/TidesDTO.php

namespace App\DTO;

final class TidesDTO implements \JsonSerializable
{
    /**
     * @var list<array{
     *             date: string,
     *             heure: string,
     *             te_cesson: float,
     *             te_bassin: float
     *           }>
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
            $this->tides[$i] = [
                "date" => $tides[$i]["date"],
                "heure" => substr($tides[$i]["heure"], 0, -3),
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
