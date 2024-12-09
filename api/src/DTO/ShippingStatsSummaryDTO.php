<?php

// Path: api/src/DTO/ShippingStatsSummaryDTO.php

declare(strict_types=1);

namespace App\DTO;

/**
 * Shipping stats summary DTO.
 * 
 * @phpstan-import-type ShippingStatsSummaryArray from \App\Repository\ShippingRepository
 */
final class ShippingStatsSummaryDTO implements \JsonSerializable
{
    /**
     * Stats summary.
     
     * @var array{Total: int, ByYear: array<int, array<int, array{nombre: int, ids: int[]}>>}
     */
    private array $stats = [
        "Total" => 0,
        "ByYear" => [],
    ];

    /**
     * Creates a ShippingStatsSummaryDTO object from raw data.
     * 
     * @param array $statsSummaryRaw
     * 
     * @phpstan-param ShippingStatsSummaryArray $statsSummaryRaw
     */
    public function __construct(private array $statsSummaryRaw)
    {
        $this->makeStats();
    }

    private function makeStats(): void
    {
        $this->stats["Total"] = count($this->statsSummaryRaw);

        $yearTemplate = \array_fill(1, 12, ["nombre" => 0, "ids" => []]);

        // Compilation du nombre de RDV par année et par mois
        foreach ($this->statsSummaryRaw as $call) {
            $date = explode("-", $call["date"]);
            $year = (int) $date[0];
            $month = (int) $date[1];

            $this->stats["ByYear"][$year] ??= $yearTemplate;

            $this->stats["ByYear"][$year][(int) $month]["nombre"]++;
            $this->stats["ByYear"][$year][(int) $month]["ids"][] = $call["id"];
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->stats;
    }
}
