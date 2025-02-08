<?php

// Path: api/src/DTO/Filter/StevedoringReportsFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

/**
 * Filter for stevedoring reports requests.
 */
final readonly class StevedoringReportsFilterDTO extends Filter
{
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
    private bool $isArchive;
    private string $shipsFilter;
    private string $portsFilter;
    private string $berthsFilter;
    private string $cargoesFilter;
    private bool $strictCargoesFilter;
    private string $customersFilter;
    private string $storageNamesFilter;

    public const DEFAULT_START_DATE = '0001-01-01';
    public const DEFAULT_END_DATE = '9999-12-31';

    public function __construct(HTTPRequestQuery $query)
    {
        $this->startDate = $query->getDatetime('startDate', self::DEFAULT_START_DATE);

        $this->endDate = $query->getDatetime('endDate', self::DEFAULT_END_DATE);

        $this->isArchive = $query->getBool('isArchive', false);

        $this->shipsFilter = trim($this->splitStringParameters($query->getString('ships')), ',');

        $this->portsFilter = trim($this->splitStringParameters($query->getString('ports')), ',');

        $this->berthsFilter = trim($this->splitStringParameters($query->getString('berths')), ',');

        $this->cargoesFilter = trim($this->splitStringParameters($query->getString('cargoes')), ',');

        $this->strictCargoesFilter = $query->getBool('strictCargoes', true);

        $this->customersFilter = trim($this->splitStringParameters($query->getString('customers')), ',');

        $this->storageNamesFilter = trim($this->splitStringParameters($query->getString('storageNames')), ',');
    }

    public function getSqlStartDate(): string
    {
        return $this->startDate->format('Y-m-d');
    }

    public function getSqlEndDate(): string
    {
        return $this->endDate->format('Y-m-d');
    }

    public function isArchive(): bool
    {
        return $this->isArchive;
    }

    public function getSqlShipsFilter(): string
    {
        return $this->shipsFilter === ""
            ? ""
            : " AND ship IN ($this->shipsFilter)";
    }

    public function getSqlPortsFilter(): string
    {
        return $this->portsFilter === ""
            ? ""
            : " AND port IN ($this->portsFilter)";
    }

    public function getSqlBerthsFilter(): string
    {
        $berthsRegexp = str_replace(',', '|', $this->berthsFilter);
        $berthsRegexp = str_replace("'", '', $berthsRegexp);

        return $this->berthsFilter === ""
            ? ""
            : " AND berth REGEXP '$berthsRegexp'";
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

    public function getSqlStorageNamesFilter(string $table = 'storage'): string
    {
        return $this->storageNamesFilter === ""
            ? ""
            : " AND {$table}.storage_name IN ($this->storageNamesFilter)";
    }

    public function getSqlFilter(): string
    {
        return $this->getSqlShipsFilter()
            . $this->getSqlPortsFilter()
            . $this->getSqlBerthsFilter()
            . $this->getSqlCargoesFilter()
            . $this->getSqlCustomersFilter()
            . $this->getSqlStorageNamesFilter();
    }
}
