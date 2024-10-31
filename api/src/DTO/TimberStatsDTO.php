<?php

// Path: api/src/DTO/TimberStatsDTO.php

namespace App\DTO;

final class TimberStatsDTO implements \JsonSerializable
{
    /**
     * Stats summary.
     
     * @var array{Total: int, ByYear: array<string, array<int, int>>}
     */
    private array $stats = [
        "Total" => 0,
        "ByYear" => [],
    ];

    /**
     * Creates a TimberStatsDTO object from raw data.
     * 
     * @param string[] $dates 
     */
    public function __construct(private array $dates)
    {
        $this->makeStats();
    }

    private function makeStats(): void
    {
        $this->stats["Total"] = count($this->dates);

        $yearTemplate = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => 0,
            12 => 0,
        ];

        // Compilation du nombre de RDV par année et par mois
        foreach ($this->dates as $date) {
            $date = explode("-", $date);
            $year = $date[0];
            $month = $date[1];

            if (!array_key_exists($year, $this->stats["ByYear"])) {
                $this->stats["ByYear"][$year] = $yearTemplate;
            };

            $this->stats["ByYear"][$year][(int) $month]++;
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->stats;
    }
}
