<?php

// Path: api/src/DTO/TempWorkHoursReportDataDTO.php

declare(strict_types=1);

namespace App\DTO;

use App\Core\Array\ArrayHandler;

class TempWorkHoursReportDataDTO
{
    /** @var array<string,
     *         array{
     *           staff: array<string, array<string, float>>,
     *           total: float,
     *         }
     *       >
     */
    private array $reportData = [];

    /**
     * @param array<array<mixed>> $rawData 
     */
    public function __construct(private array $rawData)
    {
        $this->makeReportData();
    }

    private function makeReportData(): void
    {
        foreach ($this->rawData as $data) {
            $rawDataAH = new ArrayHandler($data);

            $agency = $rawDataAH->getString('agency');
            $date = $rawDataAH->getDatetime('date');
            $staffName = $rawDataAH->getString('staffName');
            $hoursWorked = $rawDataAH->getFloat('hoursWorked');

            if (!$agency || !$date || !$staffName || !$hoursWorked) continue;

            $formattedDate = $date->format('Y-m-d');

            if (!isset($this->reportData[$agency])) {
                $this->reportData[$agency] = ['total' => 0, 'staff' => []];
            }

            if (!isset($this->reportData[$agency]['staff'][$staffName])) {
                $this->reportData[$agency]['staff'][$staffName] = ['total' => 0];
            }

            $this->reportData[$agency]['staff'][$staffName][$formattedDate] = $hoursWorked;
            $this->reportData[$agency]['staff'][$staffName]['total'] += $hoursWorked;
            $this->reportData[$agency]['total'] += $hoursWorked;
        }
    }

    /**
     * @return array<string,
     *           array{
     *             staff: array<string, array<string, float>>,
     *             total: float,
     *           }
     *         >
     */
    public function getData(): array
    {
        return $this->reportData;
    }
}
