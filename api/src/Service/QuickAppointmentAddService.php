<?php

// Path: api/src/Service/QuickAppointmentAddService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Component\Module;
use App\Entity\Config\QuickAppointmentAdd;
use App\Entity\Config\TimberQuickAppointmentAdd;
use App\Repository\QuickAppointmentAddRepository;

class QuickAppointmentAddService
{
    private QuickAppointmentAddRepository $quickAppointmentAddRepository;

    public function __construct()
    {
        $this->quickAppointmentAddRepository = new QuickAppointmentAddRepository();
    }

    public function makeTimberQuickAppointmentAddFromDatabase(array $rawData): TimberQuickAppointmentAdd
    {
        $quickAppointmentAdd = (new TimberQuickAppointmentAdd())
            ->setId($rawData["id"])
            ->setSupplier($rawData["fournisseur"])
            ->setCarrier($rawData["transporteur"])
            ->setCharterer($rawData["affreteur"])
            ->setLoading($rawData["chargement"])
            ->setCustomer($rawData["client"])
            ->setDelivery($rawData["livraison"]);

        return $quickAppointmentAdd;
    }

    public function makeTimberQuickAppointmentAddFromForm(array $rawData): TimberQuickAppointmentAdd
    {
        $quickAppointmentAdd = (new TimberQuickAppointmentAdd())
            ->setId($rawData["id"])
            ->setSupplier($rawData["fournisseur"])
            ->setCarrier($rawData["transporteur"])
            ->setCharterer($rawData["affreteur"])
            ->setLoading($rawData["chargement"])
            ->setCustomer($rawData["client"])
            ->setDelivery($rawData["livraison"]);

        return $quickAppointmentAdd;
    }

    public function quickAddExists(string $module, int $id): bool
    {
        return $this->quickAppointmentAddRepository->quickAddExists($module, $id);
    }

    // =============
    //      ALL
    // =============

    /**
     * @return array<string, Collection<covariant QuickAppointmentAdd>>
     */
    public function getAllQuickAppointmentAdds(): array
    {
        return $this->quickAppointmentAddRepository->fetchAllQuickAppointmentAdds();
    }

    // =============
    //    TIMBER 
    // =============

    /**
     * @return Collection<TimberQuickAppointmentAdd>
     */
    public function getAllTimberQuickAppointmentAdds(): Collection
    {
        return $this->quickAppointmentAddRepository->fetchAllTimberQuickAppointmentAdds();
    }

    public function getTimberQuickAppointmentAdd(int $id): ?TimberQuickAppointmentAdd
    {
        return $this->quickAppointmentAddRepository->fetchImtberQuickAppointmentAdd($id);
    }

    public function createTimberQuickAppointmentAdd(array $rawData): TimberQuickAppointmentAdd
    {
        $quickAppointmentAdd = $this->makeTimberQuickAppointmentAddFromForm($rawData);

        return $this->quickAppointmentAddRepository->createTimberQuickAppointmentAdd($quickAppointmentAdd);
    }

    public function updateTimberQuickAppointmentAdd(int $id, array $rawData): TimberQuickAppointmentAdd
    {
        $quickAdd = $this->makeTimberQuickAppointmentAddFromForm($rawData)->setId($id);

        return $this->quickAppointmentAddRepository->updateTimberQuickAppointmentAdd($quickAdd);
    }

    public function deleteTimberQuickAppointmentAdd(int $id): void
    {
        $this->quickAppointmentAddRepository->deleteTimberQuickAppointmentAdd($id);
    }
}
