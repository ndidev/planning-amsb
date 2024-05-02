<?php

namespace App\Service;

use App\Entity\Vrac\RdvVrac;
use App\Entity\Vrac\ProduitVrac;
use App\Entity\Vrac\QualiteVrac;
use App\Repository\RdvVracRepository;
use App\Repository\ProduitVracRepository;

class VracService
{
    private RdvVracRepository $rdvVracRepository;
    private ProduitVracRepository $produitRepository;

    public function __construct()
    {
        $this->rdvVracRepository = new RdvVracRepository();
        $this->produitRepository = new ProduitVracRepository();
    }

    /**
     * =======
     *   RDV
     * =======
     */

    public function makeRdvVrac(array $rawData): RdvVrac
    {
        $rdv = (new RdvVrac())
            ->setId($rawData["id"] ?? null)
            ->setDate($rawData["date_rdv"] ?? new \DateTimeImmutable("now"))
            ->setHeure($rawData["heure"] ?? null)
            ->setProduit($rawData["produit"] ?? [])
            ->setQualite($rawData["qualite"] ?? null)
            ->setQuantite($rawData["quantite"] ?? 0, $rawData["max"] ?? false)
            ->setCommandePrete($rawData["commande_prete"] ?? false)
            ->setFournisseur($rawData["fournisseur"] ?? [])
            ->setClient($rawData["client"] ?? [])
            ->setTransporteur($rawData["transporteur"] ?? null)
            ->setNumeroCommande($rawData["num_commande"] ?? "")
            ->setCommentaire($rawData["commentaire"] ?? "");

        return $rdv;
    }

    /**
     * Vérifie si un RDV vrac existe dans la base de données.
     * 
     * @param int $id Identifiant du RDV vrac.
     */
    public function rdvExiste(int $id)
    {
        return $this->rdvVracRepository->rdvExiste($id);
    }

    /**
     * Récupère tous les RDV vrac.
     * 
     * @return RdvVrac[] Tous les RDV récupérés
     */
    public function getRdvs(): array
    {
        return $this->rdvVracRepository->getRdvs();
    }

    /**
     * Récupère un RDV vrac.
     * 
     * @param int $id ID du RDV à récupérer
     * 
     * @return ?RdvVrac Rendez-vous récupéré
     */
    public function getRdv(int $id): ?RdvVrac
    {
        return $this->rdvVracRepository->getRdv($id);
    }

    /**
     * Crée un RDV vrac.
     * 
     * @param array $input Eléments du RDV à créer
     * 
     * @return RdvVrac Rendez-vous créé
     */
    public function createRdv(array $input): RdvVrac
    {
        $rdv = $this->makeRdvVrac($input);

        return $this->rdvVracRepository->createRdv($rdv);
    }

    /**
     * Met à jour un RDV vrac.
     * 
     * @param int   $id ID du RDV à modifier
     * @param array $input  Eléments du RDV à modifier
     * 
     * @return RdvVrac RDV modifié
     */
    public function updateRdv($id, array $input): RdvVrac
    {
        $rdv = $this->makeRdvVrac($input)->setId($id);

        return $this->rdvVracRepository->updateRdv($rdv);
    }

    /**
     * Met à jour certaines proriétés d'un RDV vrac.
     * 
     * @param int   $id    id du RDV à modifier
     * @param array $input Données à modifier
     * 
     * @return RdvVrac RDV modifié
     */
    public function patchRdv(int $id, array $input): RdvVrac
    {
        if (isset($input["commande_prete"])) {
            return $this->rdvVracRepository->setCommandePrete($id, (bool) $input["commande_prete"]);
        }
    }

    /**
     * Supprime un RDV vrac.
     * 
     * @param int $id ID du RDV à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteRdv(int $id): bool
    {
        return $this->rdvVracRepository->deleteRdv($id);
    }

    /**
     * ========
     * Produits
     * ========
     */

    public function makeProduit(array $rawData): ProduitVrac
    {
        $produit = (new ProduitVrac())
            ->setId($rawData["id"] ?? null)
            ->setName($rawData["nom"] ?? "")
            ->setColor($rawData["couleur"] ?? "")
            ->setUnit($rawData["unite"] ?? "")
            ->setQualites($rawData["qualites"] ?? []);

        return $produit;
    }

    /**
     * Vérifie si un produit existe dans la base de données.
     * 
     * @param int $id Identifiant du produit.
     */
    public function produitExists(int $id): bool
    {
        return $this->produitRepository->produitExiste($id);
    }

    /**
     * Récupère tous les produits vrac.
     * 
     * @return ProduitVrac[] Liste des produits vrac
     */
    public function getProduits(): array
    {
        return $this->produitRepository->getProduits();
    }

    /**
     * Récupère un produit vrac.
     * 
     * @param int $id ID du produit à récupérer
     * 
     * @return ?ProduitVrac Produit récupéré
     */
    public function getProduit(int $id): ?ProduitVrac
    {
        return $this->produitRepository->getProduit($id);
    }

    /**
     * Crée un produit vrac.
     * 
     * @param array $input Eléments du produit à créer
     * 
     * @return ProduitVrac Produit créé
     */
    public function createProduit(array $input): ProduitVrac
    {
        return $this->produitRepository->createProduit($input);
    }

    /**
     * Met à jour un produit vrac.
     * 
     * @param int   $id     ID du produit à modifier
     * @param array $input  Eléments du produit à modifier
     * 
     * @return ProduitVrac Produit modifié
     */
    public function updateProduit($id, array $input): ProduitVrac
    {
        return $this->produitRepository->updateProduit($id, $input);
    }

    /**
     * Supprime un produit vrac.
     * 
     * @param int $id ID du produit à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteProduit(int $id): bool
    {
        return $this->produitRepository->deleteProduit($id);
    }

    /**
     * ========
     * Qualités
     * ========
     */

    public function makeQualite(array $rawData): QualiteVrac
    {
        $quality = (new QualiteVrac())
            ->setId($rawData["id"] ?? null)
            ->setName($rawData["nom"] ?? "")
            ->setColor($rawData["couleur"] ?? "");

        return $quality;
    }

    /**
     * Récupère les qualités d'un produit vrac.
     * 
     * @param int $produitId ID du produit.
     * 
     * @return QualiteVrac[] Qualités récupérées.
     */
    public function getQualities(int $produitId): array
    {
        return $this->produitRepository->getQualites($produitId);
    }

    /**
     * Récupère une qualité vrac.
     * 
     * @param int $id ID de la qualité à récupérer.
     * 
     * @return ?QualiteVrac Qualité récupérée.
     */
    public function getQuality(int $id): ?QualiteVrac
    {
        return $this->produitRepository->getQualite($id);
    }
}
