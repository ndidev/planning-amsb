<?php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\ThirdParty;
use App\Service\BulkService;

class BulkAppointmentRepository extends Repository
{
    /**
     * @var BulkAppointment[]
     */
    static private array $cache = [];

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function appointmentExists(int $id): bool
    {
        return $this->mysql->exists("vrac_planning", $id);
    }

    /**
     * Fetch all the bulk appointments.
     * 
     * @return Collection<BulkAppointment> The fetched appointments.
     */
    public function getAppointments(): Collection
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
        $appointmentsRaw = $request->fetchAll();

        $bulkService = new BulkService();

        $appointments = array_map(
            fn(array $appointmentRaw) => $bulkService->makeBulkAppointmentFromDatabase($appointmentRaw),
            $appointmentsRaw
        );

        return new Collection($appointments);
    }

    /**
     * Récupère un RDV vrac.
     * 
     * @param int $id ID du RDV à récupérer
     * 
     * @return ?BulkAppointment Rendez-vous récupéré
     */
    public function getAppointment(int $id): ?BulkAppointment
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

        $bulkService = new BulkService();

        $rdv = $bulkService->makeBulkAppointmentFromDatabase($rdvRaw);

        return $rdv;
    }

    /**
     * Crée un RDV vrac.
     * 
     * @param BulkAppointment $rdv RDV à créer
     * 
     * @return BulkAppointment Rendez-vous créé
     */
    public function createAppointment(BulkAppointment $rdv): BulkAppointment
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
            'heure' => $rdv->getTime(true),
            'produit' => $rdv->getProduct()->getId(),
            'qualite' => $rdv->getQuality()?->getId(),
            'quantite' => $rdv->getQuantity()->getValue(),
            'max' => (int) $rdv->getQuantity()->isMax(),
            'commande_prete' => (int) $rdv->isReady(),
            'fournisseur' => $rdv->getSupplier()->getId(),
            'client' => $rdv->getClient()->getId(),
            'transporteur' => $rdv->getTransport()?->getId(),
            'num_commande' => $rdv->getOrderNumber(),
            'commentaire' => $rdv->getComments(),
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->getAppointment($lastInsertId);
    }

    /**
     * Met à jour un RDV vrac.
     * 
     * @param BulkAppointment $rdv RDV à modifier
     * 
     * @return BulkAppointment RDV modifié
     */
    public function updateAppointment(BulkAppointment $rdv): BulkAppointment
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
            'heure' => $rdv->getTime(true),
            'produit' => $rdv->getProduct()->getId(),
            'qualite' => $rdv->getQuality()?->getId(),
            'quantite' => $rdv->getQuantity()->getValue(),
            'max' => (int) $rdv->getQuantity()->isMax(),
            'commande_prete' => (int) $rdv->isReady(),
            'fournisseur' => $rdv->getSupplier()->getId(),
            'client' => $rdv->getClient()->getId(),
            'transporteur' => $rdv->getTransport()?->getId(),
            'num_commande' => $rdv->getOrderNumber(),
            'commentaire' => $rdv->getComments(),
            'id' => $rdv->getId(),
        ]);

        return $this->getAppointment($rdv->getId());
    }

    /**
     * Met à jour l'état de préparation d'une commande.
     * 
     * @param int   $id     id du RDV à modifier
     * @param array $status Statut de la commande
     * 
     * @return BulkAppointment RDV modifié
     */
    public function setIsReady(int $id, bool $status): BulkAppointment
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

        return $this->getAppointment($id);
    }

    /**
     * Supprime un RDV vrac.
     * 
     * @param int $id ID du RDV à supprimer
     * 
     * @throws DBException Erreur lors de la suppression.
     */
    public function deleteAppointment(int $id): void
    {
        $request = $this->mysql->prepare("DELETE FROM vrac_planning WHERE id = :id");
        $isDeleted = $request->execute(["id" => $id]);

        if (!$isDeleted) {
            throw new DBException("Erreur lors de la suppression");
        }
    }

    /**
     * Récupère les RDV vrac à exporter en PDF.
     * 
     * @return Collection<BulkAppointment> RDV vrac à exporter.
     */
    public function getPdfAppointments(
        ThirdParty $supplier,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): Collection {
        $statement =
            "SELECT
                pl.id,
                pl.date_rdv,
                SUBSTRING(pl.heure, 1, 5) AS heure,
                pl.produit,
                pl.qualite,
                pl.client,
                pl.transporteur,
                pl.num_commande
            FROM vrac_planning pl
            LEFT JOIN vrac_produits p ON p.id = pl.produit
            LEFT JOIN vrac_qualites q ON q.id = pl.qualite
            LEFT JOIN tiers c ON c.id = pl.client
            WHERE date_rdv
            BETWEEN :startDate
            AND :endDate
            AND fournisseur = :supplierId
            ORDER BY
                date_rdv,
                -heure DESC,
                p.nom,
                q.nom,
                c.nom_court";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            "supplierId" => $supplier->getId(),
            "startDate" => $startDate->format("Y-m-d"),
            "endDate" => $endDate->format("Y-m-d"),
        ]);

        $appointmentsRaw = $request->fetchAll();

        $bulkService = new BulkService();

        $appointments = array_map(
            fn(array $appointmentRaw) => $bulkService->makeBulkAppointmentFromDatabase($appointmentRaw),
            $appointmentsRaw
        );

        return new Collection($appointments);
    }
}
