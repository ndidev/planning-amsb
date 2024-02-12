<?php

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
     * @return array<int, \App\Entity\ThirdParty> Liste des tiers
     */
    public function getThirdParties(): array
    {
        return $this->thirdPartyRepository->getThirdParties();
    }

    /**
     * Récupère un tiers.
     * 
     * @param int   $id      ID du tiers à récupérer
     * @param array $options Options de récupération
     * 
     * @return ?ThirdPArty Tiers récupéré
     */
    public function getThirdParty($id): ?ThirdParty
    {
        return $this->thirdPartyRepository->getThirdParty($id);
    }

    /**
     * Crée un tiers.
     * 
     * @param array $input Eléments du tiers à créer
     * 
     * @return ThirdParty Tiers créé
     */
    public function createThirdParty(array $input): ThirdParty
    {
        return $this->thirdPartyRepository->createThirdParty($input);
    }

    /**
     * Met à jour un tiers.
     * 
     * @param int   $id ID du tiers à modifier
     * @param array $input  Eléments du tiers à modifier
     * 
     * @return ThirdParty tiers modifié
     */
    public function updateThirdParty($id, array $input): ThirdParty
    {
        return $this->thirdPartyRepository->updateThirdParty($id, $input);
    }

    /**
     * Supprime un tiers.
     * 
     * @param int $id ID du tiers à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteThirdParty(int $id): bool
    {
        return $this->thirdPartyRepository->deleteThirdParty($id);
    }

    /**
     * Récupère le nombre de RDV pour un tiers ou tous les tiers.
     * 
     * @param int $id Optionnel. ID du tiers à récupérer.
     * 
     * @return array Nombre de RDV pour le(s) tiers.
     */
    public function getThirdPartyAppointmentCount(?int $id = null): array
    {
        return $this->thirdPartyRepository->getAppointmentCount($id);
    }
}
