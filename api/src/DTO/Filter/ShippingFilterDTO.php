<?php

// Path: api/src/DTO/Filter/ShippingFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final readonly class ShippingFilterDTO extends Filter
{
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
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
     * @param HTTPRequestQuery $query
     */
    public function __construct(HTTPRequestQuery $query)
    {
        $this->startDate = $query->getDatetime('date_debut', self::DEFAULT_START_DATE);

        $this->endDate = $query->getDatetime('date_fin', self::DEFAULT_END_DATE);

        /** @var string */
        $shipFilter = preg_replace(
            "/([^,]+),?/",
            "'$1',",
            $query->getString('navire')
        );
        $this->shipFilter = trim($shipFilter, ",");

        $this->shipOwnerFilter = trim($query->getString('armateur'));

        $this->cargoFilter =
            trim($this->splitStringParameters($query->getString('marchandise')), ',');

        $this->customerFilter =
            trim($this->splitStringParameters($query->getString('client')), ',');

        $this->lastPortFilter =
            trim($this->splitStringParameters($query->getString('last_port')), ',');

        $this->nextPortFilter =
            trim($this->splitStringParameters($query->getString('next_port')), ',');
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

    public function getSqlFilter(): string
    {
        return $this->getSqlShipFilter()
            . $this->getSqlCargoFilter()
            . $this->getSqlShipOwnerFilter()
            . $this->getSqlCustomerFilter()
            . $this->getSqlLastPortFilter()
            . $this->getSqlNextPortFilter();
    }
}
