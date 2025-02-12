<?php

// Path: api/src/DTO/ShippingStatsDetailsDTO.php

declare(strict_types=1);

namespace App\DTO;

/**
 * @phpstan-type ShippingStatsDetailsArray list<array{
 *                                           id: int,
 *                                           navire: string,
 *                                           ops_date: ?string,
 *                                           etc_date: ?string,
 *                                           marchandise: string,
 *                                           client: string,
 *                                           tonnage_bl: ?float,
 *                                           tonnage_outturn: ?float,
 *                                           cubage_bl: ?float,
 *                                           cubage_outturn: ?float,
 *                                           nombre_bl: ?float,
 *                                           nombre_outturn: ?float,
 *                                         }> 
 */
final class ShippingStatsDetailsDTO implements \JsonSerializable
{
    /**
     * @var array<int, array{
     *                   id: int,
     *                   navire: string,
     *                   ops_date: ?string,
     *                   etc_date: ?string,
     *                   marchandises: array{
     *                                   marchandise: string,
     *                                   client: string,
     *                                   tonnage_outturn: ?float,
     *                                   cubage_outturn: ?float,
     *                                   nombre_outturn: ?float
     *                                 }[]
     *                   }>
     */
    private array $groupedCalls = [];

    /**
     * Creates a ShippingStatsDetailsDTO object from raw data.
     * 
     * @param array $statsDetailsRaw 
     * 
     * @phpstan-param ShippingStatsDetailsArray $statsDetailsRaw
     */
    public function __construct(private array $statsDetailsRaw)
    {
        $this->groupByCall();
    }

    private function groupByCall(): void
    {
        foreach ($this->statsDetailsRaw as $call) {
            if (!\array_key_exists($call["id"], $this->groupedCalls)) {
                $this->groupedCalls[$call["id"]] = [
                    "id" => $call["id"],
                    "navire" => $call["navire"],
                    "ops_date" => $call["ops_date"],
                    "etc_date" => $call["etc_date"],
                    "marchandises" => [],
                ];
            }

            $this->groupedCalls[$call["id"]]["marchandises"][] = [
                "marchandise" => $call["marchandise"],
                "client" => $call["client"],
                "tonnage_outturn" => $call["tonnage_outturn"] ?? $call["tonnage_bl"],
                "cubage_outturn" => $call["cubage_outturn"] ?? $call["cubage_bl"],
                "nombre_outturn" => $call["nombre_outturn"] ?? $call["nombre_bl"],
            ];
        }
    }

    public function jsonSerialize(): mixed
    {
        return \array_values($this->groupedCalls);
    }
}
