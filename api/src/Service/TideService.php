<?php

// Path: api/src/Service/TideService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Exceptions\Server\ServerException;
use App\DTO\NewTidesDTO;
use App\DTO\TidesDTO;
use App\Repository\TideRepository;

final class TideService
{
    private TideRepository $tideRepository;

    public function __construct()
    {
        $this->tideRepository = new TideRepository;
    }

    /**
     * Retrieves the tides within the specified time range.
     *
     * @param \DateTimeInterface $startDate The start date of the time range.
     * @param \DateTimeInterface $endDate   The end date of the time range.
     * 
     * @return TidesDTO Tides within the specified time range.
     */
    public function getTides(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): TidesDTO {
        return $this->tideRepository->fetchTides($startDate, $endDate);
    }

    /**
     * Retrieves an array of tides for a given year.
     *
     * @param int $year The year for which to retrieve the tides.
     * 
     * @return TidesDTO Tides for the specified year.
     */
    public function getTidesByYear(int $year): TidesDTO
    {
        return $this->tideRepository->fetchTidesByYear($year);
    }

    /**
     * Retrieves the years from the tide repository.
     *
     * @return string[] The years fetched from the tide repository.
     */
    public function getYears(): array
    {
        return $this->tideRepository->fetchYears();
    }

    /**
     * Adds tides from a file to the database.
     *
     * @param mixed $data The data containing the file information.
     * 
     * @return string The year of the first tide entry.
     */
    public function addTides(mixed $data): string
    {
        if (!\is_array($data) || !array_key_exists("tmp_name", $data)) {
            throw new ServerException("Fichier non trouvé.");
        }

        $content = \file_get_contents($data["tmp_name"]);

        if ($content === false) {
            throw new ServerException("Echec de la lecture du fichier.");
        }

        // Delete the BOM
        $content = \str_replace("\u{FEFF}", "", $content);
        // Delete the Windows carriage return
        $content = \str_replace("\r", "", $content);
        $lines = explode(PHP_EOL, $content);

        $separator = ";";

        /** @var list<array{0: string, 1: string, 2: float}> */
        $newTides = [];
        foreach ($lines as $line) {
            // Skip the line if it doesn't match the expected format (YYYY-MM-DD;HH:MM:SS;HH.HH)
            if (!\preg_match("/^\d{4}-\d{2}-\d{2};\d{2}:\d{2}:\d{2};\d+(?:\.\d+)?$/", $line)) {
                continue;
            }

            // Push each line into the tides array
            [$date, $time, $heightOfWater] = str_getcsv($line, $separator);
            /** @var string $date */
            /** @var string $time */
            /** @var string $heightOfWater */
            array_push($newTides, [
                $date,
                $time,
                (float) $heightOfWater,
            ]);
        }

        $year = \substr($newTides[0][0], 0, 4);

        $this->tideRepository->addTides($newTides);

        return $year;
    }

    /**
     * Deletes tides for a specific year.
     *
     * @param int $year The year for which to delete the tides.
     */
    public function deleteTides(int $year): void
    {
        $this->tideRepository->delete($year);
    }
}
