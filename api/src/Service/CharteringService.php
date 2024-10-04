<?php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Chartering\Charter;
use App\Entity\Chartering\CharterLeg;
use App\Repository\CharteringRepository;

class CharteringService
{
    private CharteringRepository $charteringRepository;

    public function __construct()
    {
        $this->charteringRepository = new CharteringRepository();
    }

    public function makeCharterFromDatabase(array $rawData): Charter
    {
        $charter = (new Charter())
            ->setId($rawData["id"] ?? null)
            ->setStatus($rawData["statut"] ?? 0)
            ->setLaycanStart($rawData["lc_debut"] ?? null)
            ->setLaycanEnd($rawData["lc_fin"] ?? null)
            ->setCpDate($rawData['cp_date'] ?? null)
            ->setVesselName($rawData['navire'] ?? null)
            ->setCharterer($rawData['affreteur'] ?? null)
            ->setShipOperator($rawData['armateur'] ?? null)
            ->setShipbroker($rawData['courtier'] ?? null)
            ->setFreightPayed((float) $rawData['fret_achat'] ?? 0)
            ->setFreightSold((float) $rawData['fret_vente'] ?? 0)
            ->setDemurragePayed((float) $rawData['surestaries_achat'] ?? 0)
            ->setDemurrageSold((float) $rawData['surestaries_vente'] ?? 0)
            ->setComments($rawData['commentaire'] ?? '')
            ->setArchive($rawData['archive'] ?? false)
            ->setLegs(
                array_map(
                    fn(array $leg) => $this->makeCharterLegFromDatabase($leg),
                    $rawData['legs'] ?? []
                )
            );

        return $charter;
    }

    public function makeCharterFromFormData(array $rawData): Charter
    {
        $charter = (new Charter())
            ->setId($rawData["id"] ?? null)
            ->setStatus($rawData["statut"] ?? 0)
            ->setLaycanStart($rawData["lc_debut"] ?? null)
            ->setLaycanEnd($rawData["lc_fin"] ?? null)
            ->setCpDate($rawData['cp_date'] ?? null)
            ->setVesselName($rawData['navire'] ?? null)
            ->setCharterer($rawData['affreteur'] ?? null)
            ->setShipOperator($rawData['armateur'] ?? null)
            ->setShipbroker($rawData['courtier'] ?? null)
            ->setFreightPayed((float) $rawData['fret_achat'] ?? 0)
            ->setFreightSold((float) $rawData['fret_vente'] ?? 0)
            ->setDemurragePayed((float) $rawData['surestaries_achat'] ?? 0)
            ->setDemurrageSold((float) $rawData['surestaries_vente'] ?? 0)
            ->setComments($rawData['commentaire'] ?? '')
            ->setArchive($rawData['archive'] ?? false)
            ->setLegs(
                array_map(
                    fn(array $leg) => $this->makeCharterLegFromFormData($leg),
                    $rawData['legs'] ?? []
                )
            );

        return $charter;
    }

    public function makeCharterLegFromDatabase(array $rawData): CharterLeg
    {
        $leg = (new CharterLeg())
            ->setId($rawData["id"] ?? null)
            ->setBlDate($rawData["bl_date"] ?? null)
            ->setPol($rawData["pol"] ?? null)
            ->setPod($rawData["pod"] ?? null)
            ->setCommodity($rawData["marchandise"] ?? '')
            ->setQuantity($rawData["quantite"] ?? '')
            ->setComments($rawData["commentaire"] ?? '');

        return $leg;
    }

    public function makeCharterLegFromFormData(array $rawData): CharterLeg
    {
        $leg = (new CharterLeg())
            ->setId($rawData["id"] ?? null)
            ->setCharter($rawData["charter"] ?? null)
            ->setBlDate($rawData["bl_date"] ?? null)
            ->setPol($rawData["pol"] ?? null)
            ->setPod($rawData["pod"] ?? null)
            ->setCommodity($rawData["marchandise"] ?? '')
            ->setQuantity($rawData["quantite"] ?? '')
            ->setComments($rawData["commentaire"] ?? '');

        return $leg;
    }

    /**
     * Checks if a charter exists in the database.
     * 
     * @param int $id Charter ID.
     * 
     * @return bool True if the charter exists, false otherwise.
     */
    public function charterExists(int $id): bool
    {
        return $this->charteringRepository->charterExists($id);
    }

    /**
     * Retrieves all charters.
     * 
     * @return Collection<Charter> All retrieved charters.
     */
    public function getCharters(array $filter = []): Collection
    {
        return $this->charteringRepository->fetchCharters($filter);
    }

    /**
     * Retrieves a charter.
     * 
     * @param int $id ID of the charter to retrieve.
     * 
     * @return ?Charter Retrieved charter.
     */
    public function getCharter(int $id): ?Charter
    {
        return $this->charteringRepository->fetchCharter($id);
    }

    /**
     * Creates a charter.
     * 
     * @param array $input Eléments du RDV à créer
     * 
     * @return Charter Created charter.
     */
    public function createCharter(array $input): Charter
    {
        $charter = $this->makeCharterFromFormData($input);

        return $this->charteringRepository->createCharter($charter);
    }

    /**
     * Update a bulk charter.
     * 
     * @param int   $id ID of the charter to update.
     * @param array $input  Elements of the charter to update.
     * 
     * @return Charter Updated charter.
     */
    public function updateCharter($id, array $input): Charter
    {
        $charter = $this->makeCharterFromFormData($input)->setId($id);

        return $this->charteringRepository->updateCharter($charter);
    }

    /**
     * Delete a charter.
     * 
     * @param int $id ID of the charter to delete.
     * 
     * @return bool TRUE if successful, FALSE if error.
     */
    public function deleteCharter(int $id): bool
    {
        return $this->charteringRepository->deleteCharter($id);
    }
}
