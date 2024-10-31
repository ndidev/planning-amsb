<?php

// Path: api/src/Service/QuickAppointmentAddService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Exceptions\Client\BadRequestException;
use App\Entity\Config\QuickAppointmentAdd;
use App\Entity\Config\TimberQuickAppointmentAdd;
use App\Repository\QuickAppointmentAddRepository;
use PDOException;

/**
 * @phpstan-type TimberQuickAppointmentAddArray array{
 *                                                id?: int,
 *                                                fournisseur?: int,
 *                                                transporteur?: int,
 *                                                affreteur?: int,
 *                                                chargement?: int,
 *                                                client?: int,
 *                                                livraison?: int,
 *                                              }
 */
final class QuickAppointmentAddService
{
    private QuickAppointmentAddRepository $quickAppointmentAddRepository;

    public function __construct()
    {
        $this->quickAppointmentAddRepository = new QuickAppointmentAddRepository();
    }

    /**
     * Creates a TimberQuickAppointmentAdd object from database data.
     * 
     * @param array $rawData
     * 
     * @phpstan-param TimberQuickAppointmentAddArray $rawData
     * 
     * @return TimberQuickAppointmentAdd
     */
    public function makeTimberQuickAppointmentAddFromDatabase(array $rawData): TimberQuickAppointmentAdd
    {
        $thirdPartyService = new ThirdPartyService();

        $quickAppointmentAdd = (new TimberQuickAppointmentAdd())
            ->setId($rawData["id"])
            ->setSupplier($thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setCarrier($thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
            ->setCharterer($thirdPartyService->getThirdParty($rawData["affreteur"] ?? null))
            ->setLoading($thirdPartyService->getThirdParty($rawData["chargement"] ?? null))
            ->setCustomer($thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setDelivery($thirdPartyService->getThirdParty($rawData["livraison"] ?? null));

        return $quickAppointmentAdd;
    }

    /**
     * Creates a TimberQuickAppointmentAdd object from form data.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param TimberQuickAppointmentAddArray $rawData
     * 
     * @return TimberQuickAppointmentAdd 
     */
    public function makeTimberQuickAppointmentAddFromForm(array $rawData): TimberQuickAppointmentAdd
    {
        $thirdPartyService = new ThirdPartyService();

        $quickAppointmentAdd = (new TimberQuickAppointmentAdd())
            ->setId($rawData["id"])
            ->setSupplier($thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setCarrier($thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
            ->setCharterer($thirdPartyService->getThirdParty($rawData["affreteur"] ?? null))
            ->setLoading($thirdPartyService->getThirdParty($rawData["chargement"] ?? null))
            ->setCustomer($thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setDelivery($thirdPartyService->getThirdParty($rawData["livraison"] ?? null));

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

    /**
     * Create a TimberQuickAppointmentAdd.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param TimberQuickAppointmentAddArray $rawData
     * 
     * @return TimberQuickAppointmentAdd 
     */
    public function createTimberQuickAppointmentAdd(array $rawData): TimberQuickAppointmentAdd
    {
        $quickAppointmentAdd = $this->makeTimberQuickAppointmentAddFromForm($rawData);

        if (
            !$quickAppointmentAdd->getSupplier()
            || !$quickAppointmentAdd->getCustomer()
            || !$quickAppointmentAdd->getCarrier()
            || !$quickAppointmentAdd->getCharterer()
            || !$quickAppointmentAdd->getLoading()
            || !$quickAppointmentAdd->getDelivery()
        ) {
            throw new BadRequestException("Tous les champs sont requis.");
        }

        return $this->quickAppointmentAddRepository->createTimberQuickAppointmentAdd($quickAppointmentAdd);
    }

    /**
     * Update a TimberQuickAppointmentAdd.
     * 
     * @param int $id 
     * @param array $rawData 
     * 
     * @phpstan-param TimberQuickAppointmentAddArray $rawData
     * 
     * @return TimberQuickAppointmentAdd 
     */
    public function updateTimberQuickAppointmentAdd(int $id, array $rawData): TimberQuickAppointmentAdd
    {
        $quickAppointmentAdd = $this->makeTimberQuickAppointmentAddFromForm($rawData)->setId($id);

        if (
            !$quickAppointmentAdd->getSupplier()
            || !$quickAppointmentAdd->getCustomer()
            || !$quickAppointmentAdd->getCarrier()
            || !$quickAppointmentAdd->getCharterer()
            || !$quickAppointmentAdd->getLoading()
            || !$quickAppointmentAdd->getDelivery()
        ) {
            throw new BadRequestException("Tous les champs sont requis.");
        }


        return $this->quickAppointmentAddRepository->updateTimberQuickAppointmentAdd($quickAppointmentAdd);
    }

    public function deleteTimberQuickAppointmentAdd(int $id): void
    {
        $this->quickAppointmentAddRepository->deleteTimberQuickAppointmentAdd($id);
    }
}
