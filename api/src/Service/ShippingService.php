<?php

// Path: api/src/Service/ShippingService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use App\Repository\ShippingRepository;

class ShippingService
{
    private ShippingRepository $shippingRepository;

    public function __construct()
    {
        $this->shippingRepository = new ShippingRepository();
    }

    public function makeShippingCallFromDatabase(array $rawData): ShippingCall
    {
        $shippingCall = (new ShippingCall())
            ->setId($rawData["id"] ?? null)
            ->setShipName($rawData["navire"] ?? "TBN")
            ->setVoyage($rawData["voyage"] ?? "")
            ->setShipOperator($rawData["armateur"] ?? null)
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
            ->setLastPort($rawData["last_port"] ?? null)
            ->setNextPort($rawData["next_port"] ?? null)
            ->setCallPort($rawData["call_port"] ?? "")
            ->setQuay($rawData["quai"] ?? "")
            ->setComment($rawData["commentaire"] ?? "")
            ->setCargoes(
                array_map(
                    fn(array $cargo) => $this->makeShippingCallCargoFromDatabase($cargo),
                    $rawData["marchandises"] ?? []
                )
            );

        return $shippingCall;
    }

    public function makeShippingCallFromForm(array $rawData): ShippingCall
    {
        $shippingCall = (new ShippingCall())
            ->setId($rawData["id"] ?? null)
            ->setShipName($rawData["navire"] ?? "TBN")
            ->setVoyage($rawData["voyage"] ?? "")
            ->setShipOperator($rawData["armateur"] ?? null)
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
            ->setLastPort($rawData["last_port"] ?? null)
            ->setNextPort($rawData["next_port"] ?? null)
            ->setCallPort($rawData["call_port"] ?? "")
            ->setQuay($rawData["quai"] ?? "")
            ->setComment($rawData["commentaire"] ?? "")
            ->setCargoes(
                array_map(
                    fn(array $cargo) => $this->makeShippingCallCargoFromDatabase($cargo),
                    $rawData["marchandises"] ?? []
                )
            );

        return $shippingCall;
    }

    public function makeShippingCallCargoFromDatabase(array $rawData): ShippingCallCargo
    {
        $cargo = (new ShippingCallCargo())
            ->setId($rawData["id"] ?? null)
            ->setCargoName($rawData["marchandise"] ?? '')
            ->setCustomer($rawData["client"] ?? '')
            ->setOperation($rawData["operation"] ?? '')
            ->setApproximate((bool) $rawData["environ"] ?? false)
            ->setBlTonnage(is_null($rawData["tonnage_bl"]) ? null : (float) $rawData["tonnage_bl"])
            ->setBlVolume(is_null($rawData["cubage_bl"]) ? null : (float) $rawData["cubage_bl"])
            ->setBlUnits(is_null($rawData["nombre_bl"]) ? null : (int) $rawData["nombre_bl"])
            ->setOutturnTonnage(is_null($rawData["tonnage_outturn"]) ? null : (float) $rawData["tonnage_outturn"])
            ->setOutturnVolume(is_null($rawData["cubage_outturn"]) ? null : (float) $rawData["cubage_outturn"])
            ->setOutturnUnits(is_null($rawData["nombre_outturn"]) ? null : (int) $rawData["nombre_outturn"]);

        return $cargo;
    }

    public function makeShippingCallCargoFromForm(array $rawData): ShippingCallCargo
    {
        $cargo = (new ShippingCallCargo())
            ->setId($rawData["id"] ?? null)
            ->setCargoName($rawData["marchandise"] ?? '')
            ->setCustomer($rawData["client"] ?? '')
            ->setOperation($rawData["operation"] ?? '')
            ->setApproximate((bool) $rawData["environ"] ?? false)
            ->setBlTonnage(is_null($rawData["tonnage_bl"]) ? null : (float) $rawData["tonnage_bl"])
            ->setBlVolume(is_null($rawData["cubage_bl"]) ? null : (float) $rawData["cubage_bl"])
            ->setBlVolume(is_null($rawData["nombre_bl"]) ? null : (int) $rawData["nombre_bl"])
            ->setOutturnTonnage(is_null($rawData["tonnage_outturn"]) ? null : (float) $rawData["tonnage_outturn"])
            ->setOutturnVolume(is_null($rawData["cubage_outturn"]) ? null : (float) $rawData["cubage_outturn"])
            ->setOutturnVolume(is_null($rawData["nombre_outturn"]) ? null : (int) $rawData["nombre_outturn"]);

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
    public function getShippingCalls(array $filter): Collection
    {
        return $this->shippingRepository->fetchAllCalls($filter);
    }

    public function getShippingCall(int $id): ?ShippingCall
    {
        return $this->shippingRepository->fetchCall($id);
    }

    public function createShippingCall(array $input): ShippingCall
    {
        $call = $this->makeShippingCallFromForm($input);

        return $this->shippingRepository->createCall($call);
    }

    public function updateShippingCall(int $id, array $input): ShippingCall
    {
        $call = $this->makeShippingCallFromForm($input)->setId($id);

        return $this->shippingRepository->updateCall($call);
    }

    public function deleteShippingCall(int $id): void
    {
        $this->shippingRepository->deleteCall($id);
    }

    public function getLastVoyageNumber(string $shipName, string $idAsString): string
    {
        $id = $idAsString === "" ? null : (int) $idAsString;

        return $this->shippingRepository->fetchLastVoyageNumber($shipName, $id);
    }

    public function getDraftsPerTonnage(): array
    {
        return $this->shippingRepository->fetchDraftsPerTonnage();
    }

    public function getStatsSummary(array $filter): array
    {
        return $this->shippingRepository->fetchStatsSummary($filter);
    }

    public function getStatsDetails(string|array $ids): array
    {
        $ids = is_array($ids) ? $ids : explode(",", $ids);

        return $this->shippingRepository->fetchStatsDetails($ids);
    }

    /**
     * @return string[]
     */
    public function getAllShipNames(): array
    {
        return $this->shippingRepository->fetchAllShipNames();
    }

    public function getShipsInOps(array $query): array
    {
        $startDate = isset($query['date_debut']) ? new \DateTimeImmutable($query['date_debut']) : null;
        $endDate = isset($query['date_fin']) ? new \DateTimeImmutable($query['date_fin']) : null;

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
