<?php

// Path: api/src/DTO/Filter/TimberFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final readonly class TimberFilterDTO extends Filter
{
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
    private string $supplierFilter;
    private string $customerFilter;
    private string $loadingPlaceFilter;
    private string $deliveryPlaceFilter;
    private string $transportFilter;
    private string $chartererFilter;

    public const DEFAULT_START_DATE = 'now';
    public const DEFAULT_END_DATE = '9999-12-31';

    public function __construct(HTTPRequestQuery $query)
    {
        $this->startDate = $query->getDatetime('date_debut', self::DEFAULT_START_DATE);

        $this->endDate = $query->getDatetime('date_fin', self::DEFAULT_END_DATE);

        $this->supplierFilter = trim($query->getString('fournisseur'), ',');

        $this->customerFilter = trim($query->getString('client'), ',');

        $this->loadingPlaceFilter = trim($query->getString('chargement'), ',');

        $this->deliveryPlaceFilter = trim($query->getString('livraison'), ',');

        $this->transportFilter = trim($query->getString('transporteur'), ',');

        $this->chartererFilter = trim($query->getString('affreteur'), ',');
    }

    public function getSqlStartDate(): string
    {
        return $this->startDate->format('Y-m-d');
    }

    public function getSqlEndDate(): string
    {
        return $this->endDate->format('Y-m-d');
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

    public function getSqlLoadingPlaceFilter(): string
    {
        return $this->loadingPlaceFilter === ""
            ? ""
            : " AND chargement IN ($this->loadingPlaceFilter)";
    }

    public function getSqlDeliveryPlaceFilter(): string
    {
        return $this->deliveryPlaceFilter === ""
            ? ""
            : " AND livraison IN ($this->deliveryPlaceFilter)";
    }

    public function getSqlTransportFilter(): string
    {
        return $this->transportFilter === ""
            ? ""
            : " AND transporteur IN ($this->transportFilter)";
    }

    public function getSqlChartererFilter(): string
    {
        return $this->chartererFilter === ""
            ? ""
            : " AND affreteur IN ($this->chartererFilter)";
    }

    public function getSqlFilter(): string
    {
        return $this->getSqlSupplierFilter()
            . $this->getSqlCustomerFilter()
            . $this->getSqlLoadingPlaceFilter()
            . $this->getSqlDeliveryPlaceFilter()
            . $this->getSqlTransportFilter()
            . $this->getSqlChartererFilter();
    }
}
