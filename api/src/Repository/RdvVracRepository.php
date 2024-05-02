<?php

namespace App\Repository;

use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Vrac\RdvVrac;
use App\Service\VracService;

class RdvVracRepository extends Repository
{
    /**
     * @var RdvVrac[]
     */
    static private array $cache = [];

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function rdvExiste(int $id): bool
    {
        return $this->mysql->exists("vrac_planning", $id);
    }

    /**
     * Récupère tous les RDV vrac.
     * 
     * @return RdvVrac[] Tous les RDV récupérés
     */
    public function getRdvs(): array
    {
        $statement =
            "SELECT
                id,
                date_rdv,
                SUBSTRING(heure, 1, 5) AS heure,
                produit,
                qualite,
                quantite,
                max,
                commande_prete,
                fournisseur,
                client,
                transporteur,
                num_commande,
                commentaire
            FROM vrac_planning
            ORDER BY date_rdv";

        $request = $this->mysql->query($statement);
        $rdvsRaw = $request->fetchAll();

        $vracService = new VracService();

        $rdvs = array_map(
            fn (array $rdvRaw) => $vracService->makeRdvVrac($rdvRaw),
            $rdvsRaw
        );

        return $rdvs;
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
        $statement =
            "SELECT
                id,
                date_rdv,
                SUBSTRING(heure, 1, 5) AS heure,
                produit,
                qualite,
                quantite,
                max,
                commande_prete,
                fournisseur,
                client,
                transporteur,
                num_commande,
                commentaire
            FROM vrac_planning
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(["id" => $id]);
        $rdvRaw = $request->fetch();

        if (!$rdvRaw) return null;

        $vracService = new VracService();

        $rdv = $vracService->makeRdvVrac($rdvRaw);

        return $rdv;
    }

    /**
     * Crée un RDV vrac.
     * 
     * @param RdvVrac $rdv RDV à créer
     * 
     * @return RdvVrac Rendez-vous créé
     */
    public function createRdv(RdvVrac $rdv): RdvVrac
    {
        $statement =
            "INSERT INTO vrac_planning
            SET
                date_rdv = :date_rdv,
                heure = :heure,
                produit = :produit,
                qualite = :qualite,
                quantite = :quantite,
                max = :max,
                commande_prete = :commande_prete,
                fournisseur = :fournisseur,
                client = :client,
                transporteur = :transporteur,
                num_commande = :num_commande,
                commentaire = :commentaire
                ";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'date_rdv' => $rdv->getDate(true),
            'heure' => $rdv->getHeure(true),
            'produit' => $rdv->getProduit()->getId(),
            'qualite' => $rdv->getQualite()?->getId(),
            'quantite' => $rdv->getQuantite()->getValue(),
            'max' => (int) $rdv->getQuantite()->isMax(),
            'commande_prete' => (int) $rdv->getCommandePrete(),
            'fournisseur' => $rdv->getFournisseur()->getId(),
            'client' => $rdv->getClient()->getId(),
            'transporteur' => $rdv->getTransporteur()?->getId(),
            'num_commande' => $rdv->getNumeroCommande(),
            'commentaire' => $rdv->getCommentaire(),
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->getRdv($lastInsertId);
    }

    /**
     * Met à jour un RDV vrac.
     * 
     * @param RdvVrac $rdv RDV à modifier
     * 
     * @return RdvVrac RDV modifié
     */
    public function updateRdv(RdvVrac $rdv): RdvVrac
    {
        $statement =
            "UPDATE vrac_planning
            SET
                date_rdv = :date_rdv,
                heure = :heure,
                produit = :produit,
                qualite = :qualite,
                quantite = :quantite,
                max = :max,
                commande_prete = :commande_prete,
                fournisseur = :fournisseur,
                client = :client,
                transporteur = :transporteur,
                num_commande = :num_commande,
                commentaire = :commentaire
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'date_rdv' => $rdv->getDate(true),
            'heure' => $rdv->getHeure(true),
            'produit' => $rdv->getProduit()->getId(),
            'qualite' => $rdv->getQualite()?->getId(),
            'quantite' => $rdv->getQuantite()->getValue(),
            'max' => (int) $rdv->getQuantite()->isMax(),
            'commande_prete' => (int) $rdv->getCommandePrete(),
            'fournisseur' => $rdv->getFournisseur()->getId(),
            'client' => $rdv->getClient()->getId(),
            'transporteur' => $rdv->getTransporteur()?->getId(),
            'num_commande' => $rdv->getNumeroCommande(),
            'commentaire' => $rdv->getCommentaire(),
            'id' => $rdv->getId(),
        ]);

        return $this->getRdv($rdv->getId());
    }

    /**
     * Met à jour l'état de préparation d'une commande.
     * 
     * @param int   $id     id du RDV à modifier
     * @param array $status Statut de la commande
     * 
     * @return RdvVrac RDV modifié
     */
    public function setCommandePrete(int $id, bool $status): RdvVrac
    {
        $this->mysql
            ->prepare(
                "UPDATE vrac_planning
                SET commande_prete = :commande_prete
                WHERE id = :id"
            )
            ->execute([
                'commande_prete' => (int) $status,
                'id' => $id,
            ]);

        return $this->getRdv($id);
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
        $request = $this->mysql->prepare("DELETE FROM vrac_planning WHERE id = :id");
        $success = $request->execute(["id" => $id]);

        if (!$success) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $success;
    }
}
