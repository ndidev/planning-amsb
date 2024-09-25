<?php

// Path: api/src/Service/ThirdPartyService.php

namespace App\Service;

use App\Repository\ThirdPartyRepository;
use App\Entity\ThirdParty;

class ThirdPartyService
{
    private ThirdPartyRepository $thirdPartyRepository;

    public function __construct()
    {
        $this->thirdPartyRepository = new ThirdPartyRepository();
    }

    public function makeThirdPartyFromDatabase(array $rawData): ThirdParty
    {
        $thirdParty = (new ThirdParty())
            ->setId($rawData["id"] ?? null)
            ->setShortName($rawData["nom_court"] ?? "")
            ->setFullName($rawData["nom_complet"] ?? "")
            ->setAddressLine1($rawData["adresse_ligne_1"] ?? "")
            ->setAddressLine2($rawData["adresse_ligne_2"] ?? "")
            ->setPostCode($rawData["cp"] ?? "")
            ->setCity($rawData["ville"] ?? "")
            ->setCountry($rawData["pays"] ?? [])
            ->setPhone($rawData["telephone"] ?? "")
            ->setComments($rawData["commentaire"] ?? "")
            ->setIsNonEditable($rawData["non_modifiable"] ?? false)
            ->setIsAgency($rawData["lie_agence"] ?? false)
            ->setLogo($rawData["logo"] ?? null)
            ->setIsActive($rawData["actif"] ?? true)
            ->setAppointmentCount($rawData["nombre_rdv"] ?? 0);

        foreach ($thirdParty->getRoles() as $role => $value) {
            $thirdParty->setRole($role, $rawData[$role] ?? false);
        }

        return $thirdParty;
    }

    /**
     * Vérifie si un tiers existe dans la base de données.
     * 
     * @param int $id Identifiant du tiers.
     */
    public function thirdPartyExists(int $id)
    {
        return $this->thirdPartyRepository->thirdPartyExists($id);
    }

    /**
     * Récupère tous les tiers.
     * 
     * @return ThirdParty[] Liste des tiers.
     */
    public function getThirdParties(): array
    {
        return $this->thirdPartyRepository->getThirdParties();
    }

    /**
     * Récupère un tiers.
     * 
     * @param int   $id      ID du tiers à récupérer.
     * @param array $options Options de récupération.
     * 
     * @return ?ThirdParty Tiers récupéré.
     */
    public function getThirdParty(int $id): ?ThirdParty
    {
        return $this->thirdPartyRepository->getThirdParty($id);
    }

    /**
     * Creates a third party.
     * 
     * @param array $input Elements of the third party to create.
     * 
     * @return ThirdParty Created third party.
     */
    public function createThirdParty(array $input): ThirdParty
    {
        return $this->thirdPartyRepository->createThirdParty($input);
    }

    /**
     * Updates a third party.
     * 
     * @param int   $id    ID of the third party to update.
     * @param array $input Elements of the third party to update.
     * 
     * @return ThirdParty Updated third party.
     */
    public function updateThirdParty($id, array $input): ThirdParty
    {
        return $this->thirdPartyRepository->updateThirdParty($id, $input);
    }

    /**
     * Deletes a third party.
     * 
     * @param int $id ID of the third party to delete.
     * 
     * @return bool TRUE if successful, FALSE if error.
     */
    public function deleteThirdParty(int $id): bool
    {
        return $this->thirdPartyRepository->deleteThirdParty($id);
    }

    /**
     * Retrieves the number of appointments for a third party or all third parties.
     * 
     * @param int $id Optional. ID of the third party to retrieve.
     * 
     * @return array Number of appointments for the third party(s).
     */
    public function getAppointmentCount(?int $id = null): array
    {
        return $this->thirdPartyRepository->getAppointmentCount($id);
    }
}
