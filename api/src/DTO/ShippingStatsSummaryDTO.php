<?php

// Path: api/src/DTO/ShippingStatsSummaryDTO.php

namespace App\DTO;

final class ShippingStatsSummaryDTO implements \JsonSerializable
{
    /**
     * Stats summary.
     
     * @var array{Total: int, ByYear: array<string, array<int, array{nombre: int, ids: int[]}>>}
     */
    private array $stats = [
        "Total" => 0,
        "ByYear" => [],
    ];

    /**
     * Creates a ShippingStatsSummaryDTO object from raw data.
     * 
     * @param list<array{id: int, date: string}> $calls
     */
    public function __construct(private array $calls)
    {
        $this->makeStats();
    }

    private function makeStats(): void
    {
        $this->stats["Total"] = count($this->calls);

        $yearTemplate = [
            1 => ["nombre" => 0, "ids" => []],
            2 => ["nombre" => 0, "ids" => []],
            3 => ["nombre" => 0, "ids" => []],
            4 => ["nombre" => 0, "ids" => []],
            5 => ["nombre" => 0, "ids" => []],
            6 => ["nombre" => 0, "ids" => []],
            7 => ["nombre" => 0, "ids" => []],
            8 => ["nombre" => 0, "ids" => []],
            9 => ["nombre" => 0, "ids" => []],
            10 => ["nombre" => 0, "ids" => []],
            11 => ["nombre" => 0, "ids" => []],
            12 => ["nombre" => 0, "ids" => []],
        ];

        // Compilation du nombre de RDV par année et par mois
        foreach ($this->calls as $call) {
            $date = explode("-", $call["date"]);
            $year = $date[0];
            $month = $date[1];

            if (!array_key_exists($year, $this->stats["ByYear"])) {
                $this->stats["ByYear"][$year] = $yearTemplate;
            };

            $this->stats["ByYear"][$year][(int) $month]["nombre"]++;
            $this->stats["ByYear"][$year][(int) $month]["ids"][] = $call["id"];
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->stats;
    }
}
