<?php

// Path: api/src/DTO/Filter/TimberFilterDTO.php

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final readonly class TimberFilterDTO
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

    /**
     * TimberFilterDTO constructor.
     * 
     * @param HTTPRequestQuery $query
     */
    public function __construct(HTTPRequestQuery $query)
    {
        $this->startDate = $query->getParam('date_debut', self::DEFAULT_START_DATE, 'datetime');

        $this->endDate = $query->getParam('date_fin', self::DEFAULT_END_DATE, 'datetime');

        $this->supplierFilter = trim($query->getParam('fournisseur', ''), ',');

        $this->customerFilter = trim($query->getParam('client', ''), ',');

        $this->loadingPlaceFilter = trim($query->getParam('chargement', ''), ',');

        $this->deliveryPlaceFilter = trim($query->getParam('livraison', ''), ',');

        $this->transportFilter = trim($query->getParam('transporteur', ''), ',');

        $this->chartererFilter = trim($query->getParam('affreteur', ''), ',');
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
}
