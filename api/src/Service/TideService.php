<?php

// Path: api/src/Service/TideService.php

namespace App\Service;

use App\Repository\TideRepository;

class TideService
{
    private TideRepository $tideRepository;

    public function __construct()
    {
        $this->tideRepository = new TideRepository;
    }

    /**
     * Retrieves the tides within the specified time range.
     *
     * @param string|null $start The start date of the time range (optional).
     * @param string|null $end The end date of the time range (optional).
     * @return array An array of tides within the specified time range.
     */
    public function getTides(?string $start, ?string $end): array
    {
        return $this->tideRepository->fetchTides($start, $end);
    }

    /**
     * Retrieves an array of tides for a given year.
     *
     * @param int $year The year for which to retrieve the tides.
     * @return array An array of tides for the specified year.
     */
    public function getTidesByYear(int $year): array
    {
        return $this->tideRepository->fetchTidesByYear($year);
    }

    /**
     * Retrieves the years from the tide repository.
     *
     * @return array The years fetched from the tide repository.
     */
    public function getYears(): array
    {
        return $this->tideRepository->fetchYears();
    }

    /**
     * Adds tides from a file to the database.
     *
     * @param mixed $data The data containing the file information.
     * @return string The year of the first tide entry.
     */
    public function addTides(mixed $data): string
    {
        $content = file_get_contents($data["tmp_name"]);
        // Delete the BOM
        $content = str_replace("\u{FEFF}", "", $content);
        // Delete the Windows carriage return
        $content = str_replace("\r", "", $content);
        $lines = explode(PHP_EOL, $content);

        $separator = ";";

        $tides = [];
        foreach ($lines as $line) {
            // Skip the line if it doesn't match the expected format (YYYY-MM-DD;HH:MM:SS;HH.HH)
            if (!preg_match("/^\d{4}-\d{2}-\d{2};\d{2}:\d{2}:\d{2};\d+(?:\.\d+)?$/", $line)) {
                continue;
            }

            // Push each line into the tides array
            [$date, $heure, $hauteur] = str_getcsv($line, $separator);
            array_push($tides, [
                $date,
                $heure,
                (float) $hauteur
            ]);
        }

        $year = substr($tides[0][0], 0, 4);

        $this->tideRepository->addTides($tides);

        return $year;
    }

    /**
     * Deletes tides for a specific year.
     *
     * @param int $year The year for which to delete the tides.
     * @return bool Returns true if the tides were successfully deleted, false otherwise.
     */
    public function deleteTides(int $year): bool
    {
        return $this->tideRepository->delete($year);
    }
}
