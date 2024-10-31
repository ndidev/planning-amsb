<?php

namespace App\Service;

use App\Core\Component\Collection;
use App\DTO\CharteringFilterDTO;
use App\Entity\Chartering\Charter;
use App\Entity\Chartering\CharterLeg;
use App\Repository\CharteringRepository;

/**
 * @phpstan-type CharterArray array{
 *                              id?: int,
 *                              statut?: int,
 *                              lc_debut?: string,
 *                              lc_fin?: string,
 *                              cp_date?: string,
 *                              navire?: string,
 *                              affreteur?: int,
 *                              armateur?: int,
 *                              courtier?: int,
 *                              fret_achat?: float,
 *                              fret_vente?: float,
 *                              surestaries_achat?: float,
 *                              surestaries_vente?: float,
 *                              commentaire?: string,
 *                              archive?: bool,
 *                              legs?: CharterLegArray[]
 *                            }
 * 
 * @phpstan-type CharterLegArray array{
 *                                 id?: int,
 *                                 charter?: int,
 *                                 bl_date?: string,
 *                                 pol?: string,
 *                                 pod?: string,
 *                                 marchandise?: string,
 *                                 quantite?: string,
 *                                 commentaire?: string,
 *                               }
 */
final class CharteringService
{
    private CharteringRepository $charteringRepository;

    public function __construct()
    {
        $this->charteringRepository = new CharteringRepository();
    }

    /**
     * Creates a Charter object from database data.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param CharterArray $rawData
     * 
     * @return Charter 
     */
    public function makeCharterFromDatabase(array $rawData): Charter
    {
        $thirdPartyService = new ThirdPartyService();

        $charter = (new Charter())
            ->setId($rawData["id"] ?? null)
            ->setStatus($rawData["statut"] ?? 0)
            ->setLaycanStart($rawData["lc_debut"] ?? null)
            ->setLaycanEnd($rawData["lc_fin"] ?? null)
            ->setCpDate($rawData['cp_date'] ?? null)
            ->setVesselName($rawData['navire'] ?? null)
            ->setCharterer($thirdPartyService->getThirdParty($rawData['affreteur'] ?? null))
            ->setShipOperator($thirdPartyService->getThirdParty($rawData['armateur'] ?? null))
            ->setShipbroker($thirdPartyService->getThirdParty($rawData['courtier'] ?? null))
            ->setFreightPayed((float) ($rawData['fret_achat'] ?? 0))
            ->setFreightSold((float) ($rawData['fret_vente'] ?? 0))
            ->setDemurragePayed((float) ($rawData['surestaries_achat'] ?? 0))
            ->setDemurrageSold((float) ($rawData['surestaries_vente'] ?? 0))
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

    /**
     * Creates a Charter object from form data.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param CharterArray $rawData
     * 
     * @return Charter 
     */
    public function makeCharterFromFormData(array $rawData): Charter
    {
        $thirdPartyService = new ThirdPartyService();

        $charter = (new Charter())
            ->setId($rawData["id"] ?? null)
            ->setStatus($rawData["statut"] ?? 0)
            ->setLaycanStart($rawData["lc_debut"] ?? null)
            ->setLaycanEnd($rawData["lc_fin"] ?? null)
            ->setCpDate($rawData['cp_date'] ?? null)
            ->setVesselName($rawData['navire'] ?? null)
            ->setCharterer($thirdPartyService->getThirdParty($rawData['affreteur'] ?? null))
            ->setShipOperator($thirdPartyService->getThirdParty($rawData['armateur'] ?? null))
            ->setShipbroker($thirdPartyService->getThirdParty($rawData['courtier'] ?? null))
            ->setFreightPayed((float) ($rawData['fret_achat'] ?? 0))
            ->setFreightSold((float) ($rawData['fret_vente'] ?? 0))
            ->setDemurragePayed((float) ($rawData['surestaries_achat'] ?? 0))
            ->setDemurrageSold((float) ($rawData['surestaries_vente'] ?? 0))
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

    /**
     * Creates a CharterLeg object from database data.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param CharterLegArray $rawData
     * 
     * @return CharterLeg 
     */
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

    /**
     * Creates a CharterLeg object from form data.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param CharterLegArray $rawData
     * 
     * @return CharterLeg 
     */
    public function makeCharterLegFromFormData(array $rawData): CharterLeg
    {
        $leg = (new CharterLeg())
            ->setId($rawData["id"] ?? null)
            ->setCharter($this->getCharter($rawData["charter"] ?? null))
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
     * @param CharteringFilterDTO $filter Filter to apply.
     * 
     * @return Collection<Charter> All retrieved charters.
     */
    public function getCharters(CharteringFilterDTO $filter): Collection
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
     * @param array $input Eléments de l'affrètement à créer.
     * 
     * @phpstan-param CharterArray $input
     * 
     * @return Charter Created charter.
     */
    public function createCharter(array $input): Charter
    {
        $charter = $this->makeCharterFromFormData($input);

        return $this->charteringRepository->createCharter($charter);
    }

    /**
     * Update a charter.
     * 
     * @param int   $id ID of the charter to update.
     * @param array $input  Elements of the charter to update.
     * 
     * @phpstan-param CharterArray $input
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
