<?php

// Path: api/src/Entity/Config/TimberQuickAppointmentAdd.php

declare(strict_types=1);

namespace App\Entity\Config;

use App\Core\Component\Module;
use App\Entity\ThirdParty;

class TimberQuickAppointmentAdd extends QuickAppointmentAdd
{
    private ?ThirdParty $supplier = null;
    private ?ThirdParty $carrier = null;
    private ?ThirdParty $charterer = null;
    private ?ThirdParty $loading = null;
    private ?ThirdParty $customer = null;
    private ?ThirdParty $delivery = null;

    public function __construct()
    {
        $this->setModule(Module::TIMBER);
    }

    public function setSupplier(?ThirdParty $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSupplier(): ?ThirdParty
    {
        return $this->supplier;
    }

    public function setCarrier(?ThirdParty $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function getCarrier(): ?ThirdParty
    {
        return $this->carrier;
    }

    public function setCharterer(?ThirdParty $charterer): static
    {
        $this->charterer = $charterer;

        return $this;
    }

    public function getCharterer(): ?ThirdParty
    {
        return $this->charterer;
    }

    public function setLoading(?ThirdParty $loading): static
    {
        $this->loading = $loading;

        return $this;
    }

    public function getLoading(): ?ThirdParty
    {
        return $this->loading;
    }

    public function setCustomer(?ThirdParty $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomer(): ?ThirdParty
    {
        return $this->customer;
    }

    public function setDelivery(?ThirdParty $delivery): static
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getDelivery(): ?ThirdParty
    {
        return $this->delivery;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "module" => $this->getModule(),
            "fournisseur" => $this->getSupplier()?->getId(),
            "transporteur" => $this->getCarrier()?->getId(),
            "affreteur" => $this->getCharterer()?->getId(),
            "chargement" => $this->getLoading()?->getId(),
            "client" => $this->getCustomer()?->getId(),
            "livraison" => $this->getDelivery()?->getId(),
        ];
    }
}
