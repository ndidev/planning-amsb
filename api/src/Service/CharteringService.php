<?php

// Path: api/src/Service/CharteringService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\CharterStatus;
use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\Filter\CharteringFilterDTO;
use App\Entity\Chartering\Charter;
use App\Entity\Chartering\CharterLeg;
use App\Repository\CharteringRepository;

/**
 * @phpstan-import-type CharterArray from \App\Entity\Chartering\Charter
 * @phpstan-import-type CharterLegArray from \App\Entity\Chartering\CharterLeg
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
        $rawDataAH = new ArrayHandler($rawData);

        $charter = new Charter();
        $charter->id = $rawDataAH->getInt('id');
        $charter->status = $rawDataAH->getInt('statut', CharterStatus::PENDING); // @phpstan-ignore assign.propertyType
        $charter->laycanStart = $rawDataAH->getDatetime('lc_debut');
        $charter->laycanEnd = $rawDataAH->getDatetime('lc_fin');
        $charter->cpDate = $rawDataAH->getDatetime('cp_date');
        $charter->vesselName = $rawDataAH->getString('navire');
        $charter->charterer = $this->thirdPartyService->getThirdParty($rawDataAH->getInt('affreteur'));
        $charter->shipOperator = $this->thirdPartyService->getThirdParty($rawDataAH->getInt('armateur'));
        $charter->shipbroker = $this->thirdPartyService->getThirdParty($rawDataAH->getInt('courtier'));
        $charter->freightPayed = $rawDataAH->getFloat('fret_achat', 0);
        $charter->freightSold = $rawDataAH->getFloat('fret_vente', 0);
        $charter->demurragePayed = $rawDataAH->getFloat('surestaries_achat', 0);
        $charter->demurrageSold = $rawDataAH->getFloat('surestaries_vente', 0);
        $charter->comments = $rawDataAH->getString('commentaire');
        $charter->isArchive = $rawDataAH->getBool('archive');

        /** @var CharterLegArray[] $legs */
        $legs = $rawDataAH->getArray('legs');

        $charter->legs = \array_map(fn($leg) => $this->makeCharterLegFromDatabase($leg), $legs);

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
        $charter = new Charter();
        $charter->id = $requestBody->getInt('id');
        $charter->status = $requestBody->getInt('statut', CharterStatus::PENDING); // @phpstan-ignore assign.propertyType
        $charter->laycanStart = $requestBody->getDatetime('lc_debut');
        $charter->laycanEnd = $requestBody->getDatetime('lc_fin');
        $charter->cpDate = $requestBody->getDatetime('cp_date');
        $charter->vesselName = $requestBody->getString('navire');
        $charter->charterer = $this->thirdPartyService->getThirdParty($requestBody->getInt('affreteur'));
        $charter->shipOperator = $this->thirdPartyService->getThirdParty($requestBody->getInt('armateur'));
        $charter->shipbroker = $this->thirdPartyService->getThirdParty($requestBody->getInt('courtier'));
        $charter->freightPayed = $requestBody->getFloat('fret_achat', 0);
        $charter->freightSold = $requestBody->getFloat('fret_vente', 0);
        $charter->demurragePayed = $requestBody->getFloat('surestaries_achat', 0);
        $charter->demurrageSold = $requestBody->getFloat('surestaries_vente', 0);
        $charter->comments = $requestBody->getString('commentaire');
        $charter->isArchive = $requestBody->getBool('archive');

        /** @var CharterLegArray[] $legs */
        $legs = $requestBody->getArray('legs');

        $charter->legs = \array_map(fn($leg) => $this->makeCharterLegFromFormData($leg), $legs);

        return $charter;
    }

    /**
     * Creates a CharterLeg object from database data.
     * 
     * @param CharterLegArray $rawData 
     * 
     * @return CharterLeg 
     */
    public function makeCharterLegFromDatabase(array $rawData): CharterLeg
    {
        $rawDataAH = new ArrayHandler($rawData);

        $leg = new CharterLeg();
        $leg->id = $rawDataAH->getInt('id');
        $leg->blDate = $rawDataAH->getDatetime('bl_date');
        $leg->pol = $this->portService->getPort($rawDataAH->getString('pol', null));
        $leg->pod = $this->portService->getPort($rawDataAH->getString('pod', null));
        $leg->commodity = $rawDataAH->getString('marchandise');
        $leg->quantity = $rawDataAH->getString('quantite');
        $leg->comments = $rawDataAH->getString('commentaire');

        return $leg;
    }

    /**
     * Creates a CharterLeg object from form data.
     * 
     * @param CharterLegArray $rawData 
     * 
     * @return CharterLeg 
     */
    public function makeCharterLegFromFormData(array $rawData): CharterLeg
    {
        $rawDataAH = new ArrayHandler($rawData);

        $leg = new CharterLeg();
        $leg->id = $rawDataAH->getInt('id');
        $leg->charter = $this->getCharter($rawDataAH->getInt('charter'));
        $leg->blDate = $rawDataAH->getDatetime('bl_date');
        $leg->pol = $this->portService->getPort($rawDataAH->getString('pol', null));
        $leg->pod = $this->portService->getPort($rawDataAH->getString('pod', null));
        $leg->commodity = $rawDataAH->getString('marchandise');
        $leg->quantity = $rawDataAH->getString('quantite');
        $leg->comments = $rawDataAH->getString('commentaire');

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
