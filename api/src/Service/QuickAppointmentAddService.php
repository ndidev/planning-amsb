<?php

// Path: api/src/Service/QuickAppointmentAddService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\HTTP\HTTPRequestBody;
use App\Entity\Config\QuickAppointmentAdd;
use App\Entity\Config\TimberQuickAppointmentAdd;
use App\Repository\QuickAppointmentAddRepository;

/**
 * @phpstan-import-type TimberQuickAppointmentAddArray from \App\Entity\Config\TimberQuickAppointmentAdd
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
        $rawDataAH = new ArrayHandler($rawData);

        $quickAppointmentAdd = new TimberQuickAppointmentAdd();
        $quickAppointmentAdd->id = $rawDataAH->getInt('id');
        $quickAppointmentAdd->supplier = $this->thirdPartyService->getThirdParty($rawDataAH->getInt('fournisseur'));
        $quickAppointmentAdd->carrier = $this->thirdPartyService->getThirdParty($rawDataAH->getInt('transporteur'));
        $quickAppointmentAdd->charterer = $this->thirdPartyService->getThirdParty($rawDataAH->getInt('affreteur'));
        $quickAppointmentAdd->loading = $this->thirdPartyService->getThirdParty($rawDataAH->getInt('chargement'));
        $quickAppointmentAdd->customer = $this->thirdPartyService->getThirdParty($rawDataAH->getInt('client'));
        $quickAppointmentAdd->delivery = $this->thirdPartyService->getThirdParty($rawDataAH->getInt('livraison'));

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
        $quickAppointmentAdd = new TimberQuickAppointmentAdd();
        $quickAppointmentAdd->id = $requestBody->getInt('id');
        $quickAppointmentAdd->supplier = $this->thirdPartyService->getThirdParty($requestBody->getInt('fournisseur'));
        $quickAppointmentAdd->carrier = $this->thirdPartyService->getThirdParty($requestBody->getInt('transporteur'));
        $quickAppointmentAdd->charterer = $this->thirdPartyService->getThirdParty($requestBody->getInt('affreteur'));
        $quickAppointmentAdd->loading = $this->thirdPartyService->getThirdParty($requestBody->getInt('chargement'));
        $quickAppointmentAdd->customer = $this->thirdPartyService->getThirdParty($requestBody->getInt('client'));
        $quickAppointmentAdd->delivery = $this->thirdPartyService->getThirdParty($requestBody->getInt('livraison'));

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
            !$quickAppointmentAdd->supplier
            || !$quickAppointmentAdd->carrier
            || !$quickAppointmentAdd->charterer
            || !$quickAppointmentAdd->loading
            || !$quickAppointmentAdd->customer
            || !$quickAppointmentAdd->delivery
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
            !$quickAppointmentAdd->supplier
            || !$quickAppointmentAdd->carrier
            || !$quickAppointmentAdd->charterer
            || !$quickAppointmentAdd->loading
            || !$quickAppointmentAdd->customer
            || !$quickAppointmentAdd->delivery
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
