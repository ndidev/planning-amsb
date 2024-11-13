<?php

// Path: api/src/Service/QuickAppointmentAddService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\HTTP\HTTPRequestBody;
use App\Entity\Config\QuickAppointmentAdd;
use App\Entity\Config\TimberQuickAppointmentAdd;
use App\Repository\QuickAppointmentAddRepository;

/**
 * @phpstan-import-type TimberQuickAppointmentAddArray from \App\Repository\QuickAppointmentAddRepository
 */
final class QuickAppointmentAddService
{
    private QuickAppointmentAddRepository $quickAppointmentAddRepository;
    private ThirdPartyService $thirdPartyService;

    public function __construct()
    {
        $this->quickAppointmentAddRepository = new QuickAppointmentAddRepository($this);
        $this->thirdPartyService = new ThirdPartyService();
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
        $quickAppointmentAdd = (new TimberQuickAppointmentAdd())
            ->setId($rawData["id"])
            ->setSupplier($this->thirdPartyService->getThirdParty($rawData["fournisseur"]))
            ->setCarrier($this->thirdPartyService->getThirdParty($rawData["transporteur"]))
            ->setCharterer($this->thirdPartyService->getThirdParty($rawData["affreteur"]))
            ->setLoading($this->thirdPartyService->getThirdParty($rawData["chargement"]))
            ->setCustomer($this->thirdPartyService->getThirdParty($rawData["client"]))
            ->setDelivery($this->thirdPartyService->getThirdParty($rawData["livraison"]));

        return $quickAppointmentAdd;
    }

    /**
     * Creates a TimberQuickAppointmentAdd object from form data.
     * 
     * @param HTTPRequestBody $requestBody 
     * 
     * @return TimberQuickAppointmentAdd 
     */
    public function makeTimberQuickAppointmentAddFromForm(HTTPRequestBody $requestBody): TimberQuickAppointmentAdd
    {
        $quickAppointmentAdd = (new TimberQuickAppointmentAdd())
            ->setId($requestBody->getInt('id'))
            ->setSupplier($this->thirdPartyService->getThirdParty($requestBody->getInt('fournisseur')))
            ->setCarrier($this->thirdPartyService->getThirdParty($requestBody->getInt('transporteur')))
            ->setCharterer($this->thirdPartyService->getThirdParty($requestBody->getInt('affreteur')))
            ->setLoading($this->thirdPartyService->getThirdParty($requestBody->getInt('chargement')))
            ->setCustomer($this->thirdPartyService->getThirdParty($requestBody->getInt('client')))
            ->setDelivery($this->thirdPartyService->getThirdParty($requestBody->getInt('livraison')));

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
     * @param HTTPRequestBody $rawData 
     * 
     * @return TimberQuickAppointmentAdd 
     */
    public function createTimberQuickAppointmentAdd(HTTPRequestBody $rawData): TimberQuickAppointmentAdd
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
     * @param int             $id 
     * @param HTTPRequestBody $rawData 
     * 
     * @return TimberQuickAppointmentAdd 
     */
    public function updateTimberQuickAppointmentAdd(int $id, HTTPRequestBody $rawData): TimberQuickAppointmentAdd
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
