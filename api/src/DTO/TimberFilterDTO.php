<?php

// Path: api/src/DTO/TimberFilterDTO.php

namespace App\DTO;

final readonly class TimberFilterDTO
{
    private \DateTimeImmutable $startDate;
    private \DateTimeImmutable $endDate;
    private string $supplierFilter;
    private string $customerFilter;
    private string $loadingPlaceFilter;
    private string $deliveryPlaceFilter;
    private string $transportFilter;
    private string $chartererFilter;

    public const DEFAULT_START_DATE = '0001-01-01';
    public const DEFAULT_END_DATE = '9999-12-31';

    /**
     * TimberFilterDTO constructor.
     * 
     * @param array{
     *     date_debut?: string,
     *     date_fin?: string,
     *     fournisseur?: string,
     *     client?: string,
     *     chargement?: string,
     *     livraison?: string,
     *     transporteur?: string,
     *     affreteur?: string
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

        $this->supplierFilter = trim($query['fournisseur'] ?? '', ',');

        $this->customerFilter = trim($query['client'] ?? '', ',');

        $this->loadingPlaceFilter = trim($query['chargement'] ?? '', ',');

        $this->deliveryPlaceFilter = trim($query['livraison'] ?? '', ',');

        $this->transportFilter = trim($query['transporteur'] ?? '', ',');

        $this->chartererFilter = trim($query['affreteur'] ?? '', ',');
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
