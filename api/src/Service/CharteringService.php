<?php

// Path: api/src/Service/CharteringService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Component\CharterStatus;
use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\Filter\CharteringFilterDTO;
use App\Entity\Chartering\Charter;
use App\Entity\Chartering\CharterLeg;
use App\Repository\CharteringRepository;

/**
 * @phpstan-import-type CharterArray from \App\Repository\CharteringRepository
 * @phpstan-import-type CharterLegArray from \App\Repository\CharteringRepository
 */
final class CharteringService
{
    private CharteringRepository $charteringRepository;
    private ThirdPartyService $thirdPartyService;
    private PortService $portService;

    public function __construct()
    {
        $this->charteringRepository = new CharteringRepository($this);
        $this->thirdPartyService = new ThirdPartyService();
        $this->portService = new PortService();
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
        $charter = (new Charter())
            ->setId($rawData["id"] ?? null)
            ->setStatus($rawData["statut"] ?? 0)
            ->setLaycanStart($rawData["lc_debut"] ?? null)
            ->setLaycanEnd($rawData["lc_fin"] ?? null)
            ->setCpDate($rawData['cp_date'] ?? null)
            ->setVesselName($rawData['navire'] ?? '')
            ->setCharterer($this->thirdPartyService->getThirdParty($rawData['affreteur'] ?? null))
            ->setShipOperator($this->thirdPartyService->getThirdParty($rawData['armateur'] ?? null))
            ->setShipbroker($this->thirdPartyService->getThirdParty($rawData['courtier'] ?? null))
            ->setFreightPayed((float) ($rawData['fret_achat'] ?? 0.0))
            ->setFreightSold((float) ($rawData['fret_vente'] ?? 0.0))
            ->setDemurragePayed((float) ($rawData['surestaries_achat'] ?? 0.0))
            ->setDemurrageSold((float) ($rawData['surestaries_vente'] ?? 0.0))
            ->setComments($rawData['commentaire'] ?? '')
            ->setArchive($rawData['archive'] ?? false)
            ->setLegs(
                \array_map(
                    fn(array $leg) => $this->makeCharterLegFromDatabase($leg),
                    $rawData['legs'] ?? []
                )
            );

        return $charter;
    }

    /**
     * Creates a Charter object from form data.
     * 
     * @param HTTPRequestBody $requestBody 
     * 
     * @return Charter 
     */
    public function makeCharterFromFormData(HTTPRequestBody $requestBody): Charter
    {
        $charter = (new Charter())
            ->setId($requestBody->getInt('id'))
            ->setStatus($requestBody->getInt('statut', CharterStatus::PENDING))
            ->setLaycanStart($requestBody->getDatetime('lc_debut', null))
            ->setLaycanEnd($requestBody->getDatetime('lc_fin', null))
            ->setCpDate($requestBody->getDatetime('cp_date', null))
            ->setVesselName($requestBody->getString('navire'))
            ->setCharterer($this->thirdPartyService->getThirdParty($requestBody->getInt('affreteur')))
            ->setShipOperator($this->thirdPartyService->getThirdParty($requestBody->getInt('armateur')))
            ->setShipbroker($this->thirdPartyService->getThirdParty($requestBody->getInt('courtier')))
            ->setFreightPayed($requestBody->getFloat('fret_achat', 0))
            ->setFreightSold($requestBody->getFloat('fret_vente', 0))
            ->setDemurragePayed($requestBody->getFloat('surestaries_achat', 0))
            ->setDemurrageSold($requestBody->getFloat('surestaries_vente', 0))
            ->setComments($requestBody->getString('commentaire'))
            ->setArchive($requestBody->getBool('archive'));

        /** @phpstan-var CharterLegArray[] $legs */
        $legs = $requestBody->getArray('legs');

        $charter->setLegs(
            \array_map(
                fn(array $leg) => $this->makeCharterLegFromFormData($leg),
                $legs
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
            ->setPol($this->portService->getPort($rawData["pol"] ?? null))
            ->setPod($this->portService->getPort($rawData["pod"] ?? null))
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
            ->setPol($this->portService->getPort($rawData["pol"] ?? null))
            ->setPod($this->portService->getPort($rawData["pod"] ?? null))
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
     * @param ?int $id ID of the charter to retrieve.
     * 
     * @return ?Charter Retrieved charter.
     */
    public function getCharter(?int $id): ?Charter
    {
        if ($id === null) {
            return null;
        }

        return $this->charteringRepository->fetchCharter($id);
    }

    /**
     * Creates a charter.
     * 
     * @param HTTPRequestBody $input Eléments de l'affrètement à créer.
     * 
     * @return Charter Created charter.
     */
    public function createCharter(HTTPRequestBody $input): Charter
    {
        $charter = $this->makeCharterFromFormData($input);

        return $this->charteringRepository->createCharter($charter);
    }

    /**
     * Update a charter.
     * 
     * @param int             $id ID of the charter to update.
     * @param HTTPRequestBody $input  Elements of the charter to update.
     * 
     * @return Charter Updated charter.
     */
    public function updateCharter($id, HTTPRequestBody $input): Charter
    {
        $charter = $this->makeCharterFromFormData($input)->setId($id);

        return $this->charteringRepository->updateCharter($charter);
    }

    /**
     * Delete a charter.
     * 
     * @param int $id ID of the charter to delete.
     * 
     * @return void
     * 
     * @throws DBException Erreur lors de la suppression
     */
    public function deleteCharter(int $id): void
    {
        $this->charteringRepository->deleteCharter($id);
    }
}
