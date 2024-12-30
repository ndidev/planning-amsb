<?php

// Path: api/src/DTO/Filter/ShippingFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final readonly class ShippingFilterDTO extends Filter
{
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
    private string $shipsFilter;
    private string $shipOwnersFilter;
    private string $cargoesFilter;
    private bool $strictCargoesFilter;
    private string $customersFilter;
    private string $lastPortsFilter;
    private string $nextPortsFilter;

    public const DEFAULT_START_DATE = 'now';
    public const DEFAULT_END_DATE = '9999-12-31';

    /**
     * ShippingFilterDTO constructor.
     * 
     * @param HTTPRequestQuery $query
     */
    public function __construct(HTTPRequestQuery $query)
    {
        $this->startDate = $query->getDatetime('startDate', self::DEFAULT_START_DATE);

        $this->endDate = $query->getDatetime('endDate', self::DEFAULT_END_DATE);

        $this->shipsFilter = trim($this->splitStringParameters($query->getString('ships')), ',');

        $this->shipOwnersFilter = trim($query->getString('shipOwners'));

        $this->cargoesFilter = trim($this->splitStringParameters($query->getString('cargoes')), ',');

        $this->strictCargoesFilter = $query->getBool('strictCargoes', false);

        $this->customersFilter = trim($this->splitStringParameters($query->getString('customers')), ',');

        $this->lastPortsFilter = trim($this->splitStringParameters($query->getString('lastPorts')), ',');

        $this->nextPortsFilter = trim($this->splitStringParameters($query->getString('nextPorts')), ',');
    }

    public function getSqlStartDate(): string
    {
        return $this->startDate->format('Y-m-d');
    }

    public function getSqlEndDate(): string
    {
        return $this->endDate->format('Y-m-d');
    }

    public function getSqlShipsFilter(string $table = 'cp'): string
    {
        return $this->shipsFilter === ""
            ? ""
            : " AND {$table}.navire IN ($this->shipsFilter)";
    }

    public function getSqlShipOwnersFilter(string $table = 'cp'): string
    {
        return $this->shipOwnersFilter === ""
            ? ""
            : " AND {$table}.armateur IN ($this->shipOwnersFilter)";
    }

    public function getSqlCargoesFilter(string $table = 'cem'): string
    {
        $cargoRegexp = str_replace(',', '|', $this->cargoesFilter);
        $cargoRegexp = str_replace("'", '', $cargoRegexp);

        return $this->cargoesFilter === ""
            ? ""
            : ($this->strictCargoesFilter
                ? " AND {$table}.marchandise IN ($this->cargoesFilter)"
                : " AND {$table}.marchandise REGEXP '$cargoRegexp'"
            );
    }

    public function getSqlCustomersFilter(string $table = 'cem'): string
    {
        return $this->customersFilter === ""
            ? ""
            : " AND {$table}.client IN ($this->customersFilter)";
    }

    public function getSqlLastPortsFilter(string $table = 'cp'): string
    {
        return $this->lastPortsFilter === ""
            ? ""
            : " AND {$table}.last_port IN ($this->lastPortsFilter)";
    }

    public function getSqlNextPortsFilter(string $table = 'cp'): string
    {
        return $this->nextPortsFilter === ""
            ? ""
            : " AND {$table}.next_port IN ($this->nextPortsFilter)";
    }

    public function getSqlFilter(): string
    {
        return $this->getSqlShipsFilter()
            . $this->getSqlShipOwnersFilter()
            . $this->getSqlCargoesFilter()
            . $this->getSqlCustomersFilter()
            . $this->getSqlLastPortsFilter()
            . $this->getSqlNextPortsFilter();
    }
}
