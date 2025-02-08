<?php

// Path: api/src/DTO/Filter/BulkFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final readonly class BulkFilterDTO extends Filter
{
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
    private string $productFilter;
    private string $qualityFilter;
    private string $supplierFilter;
    private string $customerFilter;
    private string $transportFilter;
    private bool $isArchive;
    private bool $isOnTv;

    public const DEFAULT_START_DATE = 'now';
    public const DEFAULT_END_DATE = '9999-12-31';

    public function __construct(HTTPRequestQuery $query)
    {
        $this->startDate = $query->getDatetime('date_debut', self::DEFAULT_START_DATE);

        $this->endDate = $query->getDatetime('date_fin', self::DEFAULT_END_DATE);

        $this->productFilter = trim($query->getString('produit'), ',');

        $this->qualityFilter = trim($query->getString('qualite'), ',');

        $this->supplierFilter = trim($query->getString('fournisseur'), ',');

        $this->transportFilter = trim($query->getString('transporteur'), ',');

        $this->customerFilter = trim($query->getString('client'), ',');

        $this->isArchive = $query->getBool('archives');

        $this->isOnTv = $query->isSet('tv');
    }

    public function getSqlStartDate(): string
    {
        return $this->startDate->format('Y-m-d');
    }

    public function getSqlEndDate(): string
    {
        return $this->endDate->format('Y-m-d');
    }

    public function getSqlProductFilter(): string
    {
        return $this->productFilter === ""
            ? ""
            : " AND produit IN ($this->productFilter)";
    }

    public function getSqlQualityFilter(): string
    {
        return $this->qualityFilter === ""
            ? ""
            : " AND qualite IN ($this->qualityFilter)";
    }

    public function getSqlSupplierFilter(): string
    {
        return $this->supplierFilter === ""
            ? ""
            : " AND fournisseur IN ($this->supplierFilter)";
    }

    public function getSqlCustomerFilter(): string
    {
        return $this->customerFilter === ""
            ? ""
            : " AND client IN ($this->customerFilter)";
    }

    public function getSqlTransportFilter(): string
    {
        return $this->transportFilter === ""
            ? ""
            : " AND transporteur IN ($this->transportFilter)";
    }

    public function isArchive(): bool
    {
        return $this->isArchive;
    }

    public function isOnTv(): bool
    {
        return $this->isOnTv;
    }

    public function getSqlTvFilter(): string
    {
        return $this->isOnTv ? " AND `show_on_tv` = 1" : "";
    }

    public function getSqlFilter(): string
    {
        return
            $this->getSqlProductFilter()
            . $this->getSqlQualityFilter()
            . $this->getSqlSupplierFilter()
            . $this->getSqlCustomerFilter()
            . $this->getSqlTransportFilter()
            . $this->getSqlTvFilter();
    }
}
