<?php

// Path: api/src/Service/ShippingService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\Filter\ShippingFilterDTO;
use App\DTO\ShippingStatsDetailsDTO;
use App\DTO\ShippingStatsSummaryDTO;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use App\Repository\ShippingRepository;

/**
 * @phpstan-import-type ShippingCallArray from \App\Entity\Shipping\ShippingCall
 * @phpstan-import-type ShippingCallCargoArray from \App\Entity\Shipping\ShippingCallCargo
 * @phpstan-import-type DraftsPerTonnage from \App\Repository\ShippingRepository
 * @phpstan-import-type ShipsInOps from \App\Repository\ShippingRepository
 */
final class ShippingService
{
    private ShippingRepository $shippingRepository;
    private StevedoringService $stevedoringService;
    private ThirdPartyService $thirdPartyService;
    private PortService $portService;

    public function __construct()
    {
        $this->shippingRepository = new ShippingRepository($this);
        $this->stevedoringService = new StevedoringService();
        $this->thirdPartyService = new ThirdPartyService();
        $this->portService = new PortService();
    }

    // =====
    // Calls
    // =====

    /**
     * Creates a ShippingCall object from database data.
     * 
     * @param ShippingCallArray $rawData Raw data from the database.
     * 
     * @return ShippingCall
     */
    public function makeShippingCallFromDatabase(array $rawData): ShippingCall
    {
        $rawDataAH = new ArrayHandler($rawData);

        $shippingCall = new ShippingCall($rawDataAH)
            ->setShipOperator($this->thirdPartyService->getThirdParty($rawDataAH->getInt('armateur')))
            ->setLastPort($this->portService->getPort($rawDataAH->getString('last_port', null)))
            ->setNextPort($this->portService->getPort($rawDataAH->getString('next_port', null)));

        $shippingCall->shipReport = $this->stevedoringService->getShipReport($rawDataAH->getInt('shipReportId'));

        /** @var ShippingCallCargoArray[] */
        $cargoesRaw = $rawDataAH->getArray('marchandises');

        $shippingCall->setCargoes(
            \array_map(
                fn($cargo) => $this->makeShippingCallCargoFromDatabase($cargo),
                $cargoesRaw
            )
        );

        return $shippingCall;
    }

    /**
     * Creates a ShippingCall object from form data.
     * 
     * @param HTTPRequestBody $requestBody Raw data from the form.
     * 
     * @return ShippingCall
     */
    public function makeShippingCallFromRequest(HTTPRequestBody $requestBody): ShippingCall
    {
        $shippingCall = new ShippingCall($requestBody)
            ->setShipOperator($this->thirdPartyService->getThirdParty($requestBody->getInt('armateur')))
            ->setLastPort($this->portService->getPort($requestBody->getString('last_port', null)))
            ->setNextPort($this->portService->getPort($requestBody->getString('next_port', null)));

        $shippingCall->shipReport = $this->stevedoringService->getShipReport($requestBody->getInt('shipReportId'));

        /** @var ShippingCallCargoArray[] */
        $cargoes = $requestBody->getArray('marchandises');

        $shippingCall->setCargoes(
            \array_map(
                fn($cargo) => $this->makeShippingCallCargoFromRequest($cargo),
                $cargoes
            )
        );

        return $shippingCall;
    }

    /**
     * Checks if a shipping call exists in the database.
     * 
     * @param int $id Shipping call ID.
     * 
     * @return bool True if the shipping call exists, false otherwise.
     */
    public function callExists(int $id): bool
    {
        return $this->shippingRepository->callExists($id);
    }

    /**
     * @return Collection<ShippingCall>
     */
    public function getShippingCalls(ShippingFilterDTO $filter): Collection
    {
        return $this->shippingRepository->fetchAllCalls($filter);
    }

    public function getShippingCall(?int $id): ?ShippingCall
    {
        if ($id === null) {
            return null;
        }

        return $this->shippingRepository->fetchCall($id);
    }

    /**
     * Creates a shipping call.
     * 
     * @param HTTPRequestBody $input Raw data from the form.
     * 
     * @return ShippingCall
     */
    public function createShippingCall(HTTPRequestBody $input): ShippingCall
    {
        $call = $this->makeShippingCallFromRequest($input);

        $call->validate();

        return $this->shippingRepository->createCall($call);
    }

    /**
     * Updates a shipping call.
     * 
     * @param int             $id 
     * @param HTTPRequestBody $input 
     * 
     * @return ShippingCall 
     */
    public function updateShippingCall(int $id, HTTPRequestBody $input): ShippingCall
    {
        $call = $this->makeShippingCallFromRequest($input)->setId($id);

        $call->validate();

        return $this->shippingRepository->updateCall($call);
    }

    public function deleteShippingCall(int $id): void
    {
        $this->shippingRepository->deleteCall($id);
    }

    public function getLastVoyageNumber(string $shipName, ?int $currentCallId): string
    {
        return $this->shippingRepository->fetchLastVoyageNumber($shipName, $currentCallId);
    }

    // =======
    // Cargoes
    // =======

    /**
     * Creates a ShippingCallCargo object from database data.
     * 
     * @param array $rawData Raw data from the database.
     * 
     * @phpstan-param ShippingCallCargoArray $rawData
     * 
     * @return ShippingCallCargo 
     */
    public function makeShippingCallCargoFromDatabase(array $rawData): ShippingCallCargo
    {
        $rawDataAH = new ArrayHandler($rawData);

        $cargo = new ShippingCallCargo();
        $cargo->id = $rawDataAH->getInt('id');
        $cargo->cargoName = $rawDataAH->getString('marchandise');
        $cargo->customer = $rawDataAH->getString('client');
        $cargo->operation = $rawDataAH->getString('operation'); // @phpstan-ignore assign.propertyType
        $cargo->isApproximate = $rawDataAH->getBool('environ');
        $cargo->blTonnage = $rawDataAH->getFloat('tonnage_bl', null);
        $cargo->blVolume = $rawDataAH->getFloat('cubage_bl', null);
        $cargo->blUnits = $rawDataAH->getInt('nombre_bl', null);
        $cargo->outturnTonnage = $rawDataAH->getFloat('tonnage_outturn', null);
        $cargo->outturnVolume = $rawDataAH->getFloat('cubage_outturn', null);
        $cargo->outturnUnits = $rawDataAH->getInt('nombre_outturn', null);

        return $cargo;
    }

    /**
     * Creates a ShippingCallCargo object from form data.
     * 
     * @param array $rawData Raw data from the form.
     * 
     * @phpstan-param ShippingCallCargoArray $rawData
     * 
     * @return ShippingCallCargo 
     */
    public function makeShippingCallCargoFromRequest(array $rawData): ShippingCallCargo
    {
        $rawDataAH = new ArrayHandler($rawData);

        $cargo = new ShippingCallCargo();
        $cargo->id = $rawDataAH->getInt('id');
        $cargo->cargoName = $rawDataAH->getString('cargoName');
        $cargo->customer = $rawDataAH->getString('customer');
        $cargo->operation = $rawDataAH->getString('operation'); // @phpstan-ignore assign.propertyType
        $cargo->isApproximate = $rawDataAH->getBool('isApproximate');
        $cargo->blTonnage = $rawDataAH->getFloat('blTonnage');
        $cargo->blVolume = $rawDataAH->getFloat('blVolume');
        $cargo->blUnits = $rawDataAH->getInt('blUnits');
        $cargo->outturnTonnage = $rawDataAH->getFloat('outturnTonnage');
        $cargo->outturnVolume = $rawDataAH->getFloat('outturnVolume');
        $cargo->outturnUnits = $rawDataAH->getInt('outturnUnits');

        return $cargo;
    }

    public function cargoEntryExists(int $id): bool
    {
        return $this->shippingRepository->cargoEntryExists($id);
    }

    public function getCargoEntry(?int $id): ?ShippingCallCargo
    {
        if ($id === null) {
            return null;
        }

        return $this->shippingRepository->fetchCargoEntry($id);
    }

    // ======
    // Others
    // ======

    /**
     * @return DraftsPerTonnage
     */
    public function getDraftsPerTonnage(): array
    {
        return $this->shippingRepository->fetchDraftsPerTonnage();
    }

    /**
     * @param ShippingFilterDTO $filter 
     * 
     * @return ShippingStatsSummaryDTO
     */
    public function getStatsSummary(ShippingFilterDTO $filter): ShippingStatsSummaryDTO
    {
        return $this->shippingRepository->fetchStatsSummary($filter);
    }

    /**
     * Get the details of the shipping stats.
     * 
     * @param string|int[] $ids 
     * 
     * @return ShippingStatsDetailsDTO 
     */
    public function getStatsDetails(string|array $ids): ShippingStatsDetailsDTO
    {
        $ids = \is_array($ids)
            ? $ids
            : \array_map(fn(string $id) => (int) $id, \explode(",", $ids));

        return $this->shippingRepository->fetchStatsDetails($ids);
    }

    /**
     * @return string[]
     */
    public function getAllShipNames(): array
    {
        return $this->shippingRepository->fetchAllShipNames();
    }

    /**
     * @return ShipsInOps
     */
    public function getShipsInOps(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): array {
        return $this->shippingRepository->fetchShipsInOps($startDate, $endDate);
    }

    /**
     * @return string[]
     */
    public function getAllCargoNames(): array
    {
        return $this->shippingRepository->fetchAllCargoNames();
    }

    /**
     * @return string[]
     */
    public function getAllCustomersNames(): array
    {
        return $this->shippingRepository->fetchAllCustomersNames();
    }
}
