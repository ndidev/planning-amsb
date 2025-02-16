<?php

// Path: api/src/Entity/Config/TimberQuickAppointmentAdd.php

declare(strict_types=1);

namespace App\Entity\Config;

use App\Core\Validation\Constraints\Required;
use App\Core\Component\Module;
use App\Entity\ThirdParty;

/**
 * @phpstan-type TimberQuickAppointmentAddArray array{
 *                                                id: int|null,
 *                                                fournisseur: int,
 *                                                transporteur: int,
 *                                                affreteur: int,
 *                                                chargement: int,
 *                                                client: int,
 *                                                livraison: int,
 *                                              }
 */
final class TimberQuickAppointmentAdd extends QuickAppointmentAdd
{
    #[Required("Le fournisseur est obligatoire.")]
    private ?ThirdParty $supplier = null;

    private ?ThirdParty $carrier = null;

    private ?ThirdParty $charterer = null;

    #[Required("Le lieu chargement est obligatoire.")]
    private ?ThirdParty $loading = null;

    #[Required("Le client est obligatoire.")]
    private ?ThirdParty $customer = null;

    private ?ThirdParty $delivery = null;

    public function __construct()
    {
        $this->module = Module::TIMBER;
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

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "module" => $this->module,
            "fournisseur" => $this->getSupplier()?->id,
            "transporteur" => $this->getCarrier()?->id,
            "affreteur" => $this->getCharterer()?->id,
            "chargement" => $this->getLoading()?->id,
            "client" => $this->getCustomer()?->id,
            "livraison" => $this->getDelivery()?->id,
        ];
    }
}
