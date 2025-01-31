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

    public const DEFAULT_END_DATE = '9999-12-31';

    public function __construct(HTTPRequestQuery $query)
    {
        $defaultStartDate = (new \DateTime())->sub(new \DateInterval('P1Y'));
        $this->startDate = $query->getDatetime('startDate', $defaultStartDate);

        $this->endDate = $query->getDatetime('endDate', self::DEFAULT_END_DATE);

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
