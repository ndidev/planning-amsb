<?php

// Path: api/src/DTO/Filter/CharteringFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final class CharteringFilterDTO
{
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
    private string $statusFilter;
    private string $chartererFilter;
    private string $ownerFilter;
    private string $brokerFilter;
    private bool $isArchive;

    public const DEFAULT_START_DATE = '0001-01-01';
    public const DEFAULT_END_DATE = '9999-12-31';

    /**
     * CharteringFilterDTO constructor.
     * 
     * @param HTTPRequestQuery $query
     */
    public function __construct(HTTPRequestQuery $query)
    {
        $this->startDate = $query->getDatetime('date_debut', self::DEFAULT_START_DATE);

        $this->endDate = $query->getDatetime('date_fin', self::DEFAULT_END_DATE);

        $this->statusFilter = trim($query->getString('statut'), ',');

        $this->chartererFilter = trim($query->getString('affreteur'), ',');

        $this->ownerFilter = trim($query->getString('armateur'), ',');

        $this->brokerFilter = trim($query->getString('courtier'), ',');

        $this->isArchive = $query->isSet('archives');
    }

    public function getSqlStartDate(): string
    {
        return $this->startDate->format('Y-m-d');
    }

    public function getSqlEndDate(): string
    {
        return $this->endDate->format('Y-m-d');
    }

    public function getSqlStatusFilter(): string
    {
        return $this->statusFilter === ""
            ? ""
            : " AND statut IN ($this->statusFilter)";
    }

    public function getSqlChartererFilter(): string
    {
        return $this->chartererFilter === ""
            ? ""
            : " AND affreteur IN ($this->chartererFilter)";
    }

    public function getSqlOwnerFilter(): string
    {
        return $this->ownerFilter === ""
            ? ""
            : " AND armateur IN ($this->ownerFilter)";
    }

    public function getSqlBrokerFilter(): string
    {
        return $this->brokerFilter === ""
            ? ""
            : " AND courtier IN ($this->brokerFilter)";
    }

    public function isArchive(): bool
    {
        return $this->isArchive;
    }
}
