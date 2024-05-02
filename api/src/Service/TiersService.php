<?php

namespace App\Service;

use App\Repository\TiersRepository;
use App\Entity\Tiers;

class TiersService
{
    private TiersRepository $tiersRepository;

    public function __construct()
    {
        $this->tiersRepository = new TiersRepository();
    }

    /**
     * Vérifie si un tiers existe dans la base de données.
     * 
     * @param int $id Identifiant du tiers.
     */
    public function tiersExiste(int $id)
    {
        return $this->tiersRepository->tiersExiste($id);
    }

    /**
     * Récupère tous les tiers.
     * 
     * @return Tiers[] Liste des tiers
     */
    public function getListeTiers(): array
    {
        return $this->tiersRepository->getListeTiers();
    }

    /**
     * Récupère un tiers.
     * 
     * @param int   $id      ID du tiers à récupérer
     * @param array $options Options de récupération
     * 
     * @return ?Tiers Tiers récupéré
     */
    public function getTiers(int $id): ?Tiers
    {
        return $this->tiersRepository->getTiers($id);
    }

    /**
     * Crée un tiers.
     * 
     * @param array $input Eléments du tiers à créer
     * 
     * @return Tiers Tiers créé
     */
    public function createThirdParty(array $input): Tiers
    {
        return $this->tiersRepository->createThirdParty($input);
    }

    /**
     * Met à jour un tiers.
     * 
     * @param int   $id ID du tiers à modifier
     * @param array $input  Eléments du tiers à modifier
     * 
     * @return Tiers tiers modifié
     */
    public function updateTiers($id, array $input): Tiers
    {
        return $this->tiersRepository->updateTiers($id, $input);
    }

    /**
     * Supprime un tiers.
     * 
     * @param int $id ID du tiers à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteTiers(int $id): bool
    {
        return $this->tiersRepository->deleteTiers($id);
    }

    /**
     * Récupère le nombre de RDV pour un tiers ou tous les tiers.
     * 
     * @param int $id Optionnel. ID du tiers à récupérer.
     * 
     * @return array Nombre de RDV pour le(s) tiers.
     */
    public function getNombreRdvTiers(?int $id = null): array
    {
        return $this->tiersRepository->getNombreRdv($id);
    }
}
