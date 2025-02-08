<?php

// Path: api/src/DTO/Filter/BulkDispatchStatsFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final readonly class BulkDispatchStatsFilterDTO extends Filter
{
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
    private string $staffFilter;

    public function __construct(HTTPRequestQuery $query)
    {
        $year = (int) new \DateTime()->format('Y');

        $defaultStartDate = new \DateTime()->setDate($year, 1, 1);
        $this->startDate = $query->getDatetime('startDate', $defaultStartDate);

        $defaultEndDate = new \DateTime()->setDate($year, 12, 31);
        $this->endDate = $query->getDatetime('endDate', $defaultEndDate);

        $this->staffFilter = trim($query->getString('staff'), ',');
    }

    public function getSqlStartDate(): string
    {
        return $this->startDate->format('Y-m-d');
    }

    public function getSqlEndDate(): string
    {
        return $this->endDate->format('Y-m-d');
    }

    public function getSqlStaffFilter(): string
    {
        return $this->staffFilter === ""
            ? ""
            : " AND staff_id IN ($this->staffFilter)";
    }

    public function getSqlFilter(): string
    {
        return $this->getSqlStaffFilter();
    }
}
