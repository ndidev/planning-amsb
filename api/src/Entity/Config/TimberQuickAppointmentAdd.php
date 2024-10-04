<?php

// Path: api/src/Entity/Config/TimberQuickAppointmentAdd.php

namespace App\Entity\Config;

use App\Core\Component\Module;
use App\Entity\ThirdParty;
use App\Service\ThirdPartyService;

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
        $this->module = Module::TIMBER;
    }

    public function getSupplier(): ?ThirdParty
    {
        return $this->supplier;
    }

    public function setSupplier(ThirdParty|int|null $supplier): static
    {
        if (is_int($supplier)) {
            $this->supplier = (new ThirdPartyService())->getThirdParty($supplier);
        } else {
            $this->supplier = $supplier;
        }

        return $this;
    }

    public function getCarrier(): ?ThirdParty
    {
        return $this->carrier;
    }

    public function setCarrier(ThirdParty|int|null $carrier): static
    {
        if (is_int($carrier)) {
            $this->carrier = (new ThirdPartyService())->getThirdParty($carrier);
        } else {
            $this->carrier = $carrier;
        }

        return $this;
    }

    public function getCharterer(): ?ThirdParty
    {
        return $this->charterer;
    }

    public function setCharterer(ThirdParty|int|null $charterer): static
    {
        if (is_int($charterer)) {
            $this->charterer = (new ThirdPartyService())->getThirdParty($charterer);
        } else {
            $this->charterer = $charterer;
        }

        return $this;
    }

    public function getLoading(): ?ThirdParty
    {
        return $this->loading;
    }

    public function setLoading(ThirdParty|int|null $loading): static
    {
        if (is_int($loading)) {
            $this->loading = (new ThirdPartyService())->getThirdParty($loading);
        } else {
            $this->loading = $loading;
        }

        return $this;
    }

    public function getCustomer(): ?ThirdParty
    {
        return $this->customer;
    }

    public function setCustomer(ThirdParty|int|null $customer): static
    {
        if (is_int($customer)) {
            $this->customer = (new ThirdPartyService())->getThirdParty($customer);
        } else {
            $this->customer = $customer;
        }

        return $this;
    }

    public function getDelivery(): ?ThirdParty
    {
        return $this->delivery;
    }

    public function setDelivery(ThirdParty|int|null $delivery): static
    {
        if (is_int($delivery)) {
            $this->delivery = (new ThirdPartyService())->getThirdParty($delivery);
        } else {
            $this->delivery = $delivery;
        }

        return $this;
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
