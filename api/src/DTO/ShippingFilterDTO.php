<?php

// Path: api/src/DTO/ShippingFilterDTO.php

namespace App\DTO;

final readonly class ShippingFilterDTO
{
    private \DateTimeImmutable $startDate;
    private \DateTimeImmutable $endDate;
    private string $shipFilter;
    private string $shipOwnerFilter;
    private string $cargoFilter;
    private string $customerFilter;
    private string $lastPortFilter;
    private string $nextPortFilter;

    public const DEFAULT_START_DATE = '0001-01-01';
    public const DEFAULT_END_DATE = '9999-12-31';

    /**
     * ShippingFilterDTO constructor.
     * 
     * @param array{
     *     date_debut?: string,
     *     date_fin?: string,
     *     navire?: string,
     *     armateur?: string,
     *     marchandise?: string,
     *     client?: string,
     *     last_port?: string,
     *     next_port?: string
     * } $query 
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

        $this->shipFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $query['navire'] ?? ""), ",");

        $this->shipOwnerFilter = trim($query['armateur'] ?? "", ",");

        $this->cargoFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $query['marchandise'] ?? ""), ",");

        $this->customerFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $query['client'] ?? ""), ",");

        $this->lastPortFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $query['last_port'] ?? ""), ",");

        $this->nextPortFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $query['next_port'] ?? ""), ",");
    }

    public function getSqlStartDate(): string
    {
        return $this->startDate->format('Y-m-d');
    }

    public function getSqlEndDate(): string
    {
        return $this->endDate->format('Y-m-d');
    }

    public function getSqlShipFilter(string $table = 'cp'): string
    {
        return $this->shipFilter === ""
            ? ""
            : " AND {$table}.navire IN ($this->shipFilter)";
    }

    public function getSqlShipOwnerFilter(string $table = 'cp'): string
    {
        return $this->shipOwnerFilter === ""
            ? ""
            : " AND {$table}.armateur IN ($this->shipOwnerFilter)";
    }

    public function getSqlCargoFilter(string $table = 'cem'): string
    {
        return $this->cargoFilter === ""
            ? ""
            : " AND {$table}.marchandise IN ($this->cargoFilter)";
    }

    public function getSqlCustomerFilter(string $table = 'cem'): string
    {
        return $this->customerFilter === ""
            ? ""
            : " AND {$table}.client IN ($this->customerFilter)";
    }

    public function getSqlLastPortFilter(string $table = 'cp'): string
    {
        return $this->lastPortFilter === ""
            ? ""
            : " AND {$table}.last_port IN ($this->lastPortFilter)";
    }

    public function getSqlNextPortFilter(string $table = 'cp'): string
    {
        return $this->nextPortFilter === ""
            ? ""
            : " AND {$table}.next_port IN ($this->nextPortFilter)";
    }
}
