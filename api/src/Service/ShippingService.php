<?php

// Path: api/src/Service/ShippingService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\Filter\ShippingFilterDTO;
use App\DTO\ShippingStatsDetailsDTO;
use App\DTO\ShippingStatsSummaryDTO;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use App\Repository\ShippingRepository;

/**
 * @phpstan-import-type ShippingCallArray from \App\Repository\ShippingRepository
 * @phpstan-import-type ShippingCallCargoArray from \App\Repository\ShippingRepository
 * @phpstan-import-type DraftsPerTonnage from \App\Repository\ShippingRepository
 * @phpstan-import-type ShipsInOps from \App\Repository\ShippingRepository
 */
final class ShippingService
{
    private ShippingRepository $shippingRepository;
    private ThirdPartyService $thirdPartyService;
    private PortService $portService;

    public function __construct()
    {
        $this->shippingRepository = new ShippingRepository($this);
        $this->thirdPartyService = new ThirdPartyService();
        $this->portService = new PortService();
    }

    /**
     * Creates a ShippingCall object from database data.
     * 
     * @param array $rawData Raw data from the database.
     * 
     * @phpstan-param ShippingCallArray $rawData
     * 
     * @return ShippingCall
     */
    public function makeShippingCallFromDatabase(array $rawData): ShippingCall
    {
        $shippingCall = (new ShippingCall())
            ->setId($rawData["id"] ?? null)
            ->setShipName($rawData["navire"] ?? "TBN")
            ->setVoyage($rawData["voyage"] ?? "")
            ->setShipOperator($this->thirdPartyService->getThirdParty($rawData["armateur"] ?? null))
            ->setEtaDate($rawData["eta_date"] ?? null)
            ->setEtaTime($rawData["eta_heure"] ?? "")
            ->setNorDate($rawData["nor_date"] ?? null)
            ->setNorTime($rawData["nor_heure"] ?? "")
            ->setPobDate($rawData["pob_date"] ?? null)
            ->setPobTime($rawData["pob_heure"] ?? "")
            ->setEtbDate($rawData["etb_date"] ?? null)
            ->setEtbTime($rawData["etb_heure"] ?? "")
            ->setOpsDate($rawData["ops_date"] ?? null)
            ->setOpsTime($rawData["ops_heure"] ?? "")
            ->setEtcDate($rawData["etc_date"] ?? null)
            ->setEtcTime($rawData["etc_heure"] ?? "")
            ->setEtdDate($rawData["etd_date"] ?? null)
            ->setEtdTime($rawData["etd_heure"] ?? "")
            ->setArrivalDraft($rawData["te_arrivee"] ?? null)
            ->setDepartureDraft($rawData["te_depart"] ?? null)
            ->setLastPort($this->portService->getPort($rawData["last_port"] ?? null))
            ->setNextPort($this->portService->getPort($rawData["next_port"] ?? null))
            ->setCallPort($rawData["call_port"] ?? "")
            ->setQuay($rawData["quai"] ?? "")
            ->setComment($rawData["commentaire"] ?? "")
            ->setCargoes(
                \array_map(
                    fn(array $cargo) => $this->makeShippingCallCargoFromDatabase($cargo),
                    $rawData["marchandises"] ?? []
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
    public function makeShippingCallFromForm(HTTPRequestBody $requestBody): ShippingCall
    {
        $shippingCall = (new ShippingCall())
            ->setId($requestBody->getInt('id'))
            ->setShipName($requestBody->getString('navire', 'TBN'))
            ->setVoyage($requestBody->getString('voyage'))
            ->setShipOperator($this->thirdPartyService->getThirdParty($requestBody->getInt('armateur')))
            ->setEtaDate($requestBody->getDatetime('eta_date'))
            ->setEtaTime($requestBody->getString('eta_heure'))
            ->setNorDate($requestBody->getDatetime('nor_date'))
            ->setNorTime($requestBody->getString('nor_heure'))
            ->setPobDate($requestBody->getDatetime('pob_date'))
            ->setPobTime($requestBody->getString('pob_heure'))
            ->setEtbDate($requestBody->getDatetime('etb_date'))
            ->setEtbTime($requestBody->getString('etb_heure'))
            ->setOpsDate($requestBody->getDatetime('ops_date'))
            ->setOpsTime($requestBody->getString('ops_heure'))
            ->setEtcDate($requestBody->getDatetime('etc_date'))
            ->setEtcTime($requestBody->getString('etc_heure'))
            ->setEtdDate($requestBody->getDatetime('etd_date'))
            ->setEtdTime($requestBody->getString('etd_heure'))
            ->setArrivalDraft($requestBody->getFloat('te_arrivee'))
            ->setDepartureDraft($requestBody->getFloat('te_depart'))
            ->setLastPort($this->portService->getPort($requestBody->getString('last_port', null)))
            ->setNextPort($this->portService->getPort($requestBody->getString('next_port', null)))
            ->setCallPort($requestBody->getString('call_port'))
            ->setQuay($requestBody->getString('quai'))
            ->setComment($requestBody->getString('commentaire'));

        /** @phpstan-var ShippingCallCargoArray[] $cargoes */
        $cargoes = $requestBody->getArray('marchandises');

        $shippingCall->setCargoes(
            \array_map(
                fn(array $cargo) => $this->makeShippingCallCargoFromForm($cargo),
                $cargoes
            )
        );

        return $shippingCall;
    }

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
        $cargo = (new ShippingCallCargo())
            ->setId($rawData["id"] ?? null)
            ->setCargoName($rawData["marchandise"] ?? '')
            ->setCustomer($rawData["client"] ?? '')
            ->setOperation($rawData["operation"] ?? '')
            ->setApproximate((bool) ($rawData["environ"] ?? false))
            ->setBlTonnage(isset($rawData["tonnage_bl"]) ? (float) $rawData["tonnage_bl"] : null)
            ->setBlVolume(isset($rawData["cubage_bl"]) ? (float) $rawData["cubage_bl"] : null)
            ->setBlUnits(isset($rawData["nombre_bl"]) ? (int) $rawData["nombre_bl"] : null)
            ->setOutturnTonnage(isset($rawData["tonnage_outturn"]) ? (float) $rawData["tonnage_outturn"] : null)
            ->setOutturnVolume(isset($rawData["cubage_outturn"]) ? (float) $rawData["cubage_outturn"] : null)
            ->setOutturnUnits(isset($rawData["nombre_outturn"]) ? (int) $rawData["nombre_outturn"] : null);

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
    public function makeShippingCallCargoFromForm(array $rawData): ShippingCallCargo
    {
        $cargo = (new ShippingCallCargo())
            ->setId($rawData["id"] ?? null)
            ->setCargoName($rawData["marchandise"] ?? '')
            ->setCustomer($rawData["client"] ?? '')
            ->setOperation($rawData["operation"] ?? '')
            ->setApproximate((bool) ($rawData["environ"] ?? false))
            ->setBlTonnage(isset($rawData["tonnage_bl"]) ? (float) $rawData["tonnage_bl"] : null)
            ->setBlVolume(isset($rawData["cubage_bl"]) ? (float) $rawData["cubage_bl"] : null)
            ->setBlVolume(isset($rawData["nombre_bl"]) ? (int) $rawData["nombre_bl"] : null)
            ->setOutturnTonnage(isset($rawData["tonnage_outturn"]) ? (float) $rawData["tonnage_outturn"] : null)
            ->setOutturnVolume(isset($rawData["cubage_outturn"]) ? (float) $rawData["cubage_outturn"] : null)
            ->setOutturnVolume(isset($rawData["nombre_outturn"]) ? (int) $rawData["nombre_outturn"] : null);

        return $cargo;
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
        return $this->shippingRepository->exists($id);
    }

    /**
     * @return Collection<ShippingCall>
     */
    public function getShippingCalls(bool $archives = false): Collection
    {
        return $this->shippingRepository->fetchAllCalls($archives);
    }

    public function getShippingCall(int $id): ?ShippingCall
    {
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
        $call = $this->makeShippingCallFromForm($input);

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
        $call = $this->makeShippingCallFromForm($input)->setId($id);

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

    /**
     * @phpstan-return DraftsPerTonnage
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
            : \array_map(fn(string $id) => (int) $id, explode(",", $ids));

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
     * @return array
     * 
     * @phpstan-return ShipsInOps
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
