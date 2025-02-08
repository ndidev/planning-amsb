<?php

// Path: api/src/DTO/Filter/StevedoringStaffFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final readonly class StevedoringStaffFilterDTO extends Filter
{
    private string $contractType;
    private string $agencyFilter;

    public function __construct(HTTPRequestQuery $query)
    {
        $this->contractType = trim($this->splitStringParameters($query->getString('type')), ',');

        $this->agencyFilter = trim($this->splitStringParameters($query->getString('agency')), ',');
    }

    public function getSqlContractTypeFilter(): string
    {
        return $this->contractType === ""
            ? ""
            : " AND `type` IN ($this->contractType)";
    }

    public function getSqlAgencyFilter(): string
    {
        return $this->agencyFilter === ""
            ? ""
            : " AND temp_work_agency IN ($this->agencyFilter)";
    }

    public function getSqlFilter(): string
    {
        return $this->getSqlContractTypeFilter()
            . $this->getSqlAgencyFilter();
    }
}
