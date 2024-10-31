<?php

// Path: api/src/DTO/CharteringFilterDTO.php

namespace App\DTO;

final class CharteringFilterDTO
{
    private \DateTimeImmutable $startDate;
    private \DateTimeImmutable $endDate;
    private string $statusFilter;
    private string $chartererFilter;
    private string $ownerFilter;
    private string $brokerFilter;
    private bool $isArchive;

    private const DEFAULT_START_DATE = '0001-01-01';
    private const DEFAULT_END_DATE = '9999-12-31';

    /**
     * CharteringFilterDTO constructor.
     * 
     * @param array{
     *          date_debut?: string,
     *          date_fin?: string,
     *          statut?: string,
     *          affreteur?: string,
     *          armateur?: string,
     *          courtier?: string,
     *          archives?: string
     *        } $query 
     */
    public function __construct(array $query)
    {
        $this->startDate = new \DateTimeImmutable(
            isset($query['date_debut'])
                ? ($query['date_debut'] ?: self::DEFAULT_START_DATE)
                : self::DEFAULT_START_DATE
        );

        $this->endDate = new \DateTimeImmutable(
            isset($query['date_fin'])
                ? ($query['date_fin'] ?: self::DEFAULT_END_DATE)
                : self::DEFAULT_END_DATE
        );

        $this->statusFilter = trim($query['statut'] ?? '', ',');

        $this->chartererFilter = trim($query['affreteur'] ?? '', ',');

        $this->ownerFilter = trim($query['armateur'] ?? '', ',');

        $this->brokerFilter = trim($query['courtier'] ?? '', ',');

        $this->isArchive = array_key_exists('archives', $query);
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
