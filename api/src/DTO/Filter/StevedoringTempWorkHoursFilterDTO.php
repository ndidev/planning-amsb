<?php

// Path: api/src/DTO/Filter/StevedoringTempWorkHoursFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final readonly class StevedoringTempWorkHoursFilterDTO extends Filter
{
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
    private string $staffFilter;
    private string $agencyFilter;

    public const DEFAULT_START_DATE = 'now';
    public const DEFAULT_END_DATE = '9999-12-31';

    /**
     * TimberFilterDTO constructor.
     * 
     * @param HTTPRequestQuery $query
     */
    public function __construct(HTTPRequestQuery $query)
    {
        $this->startDate = $query->getDatetime('startDate', self::DEFAULT_START_DATE);

        $this->endDate = $query->getDatetime('endDate', self::DEFAULT_END_DATE);

        $this->staffFilter = trim($query->getString('staff'), ',');

        $this->agencyFilter = trim($this->splitStringParameters($query->getString('agency')), ',');
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

    public function getSqlAgencyFilter(): string
    {
        return $this->agencyFilter === ""
            ? ""
            : " AND temp_work_agency IN ($this->agencyFilter)";
    }

    public function getSqlFilter(): string
    {
        return $this->getSqlStaffFilter()
            . $this->getSqlAgencyFilter();
    }
}
