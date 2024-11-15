<?php

// Path: api/src/Repository/BulkAppointmentRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\ThirdParty;
use App\Service\BulkService;

/**
 * @phpstan-type BulkAppointmentArray array{
 *                                      id: int,
 *                                      date_rdv: string,
 *                                      heure: ?string,
 *                                      produit: int,
 *                                      qualite: ?int,
 *                                      quantite: int,
 *                                      max: int,
 *                                      commande_prete: int,
 *                                      fournisseur: int,
 *                                      client: int,
 *                                      transporteur: ?int,
 *                                      num_commande: string,
 *                                      commentaire: string,
 *                                    }
 */
final class BulkAppointmentRepository extends Repository
{
    public function __construct(private BulkService $bulkService)
    {
        parent::__construct();
    }

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

        if (!$request) {
            throw new DBException("Impossible de récupérer les RDV vrac.");
        }

        /** @phpstan-var BulkAppointmentArray[] $appointmentsRaw */
        $appointmentsRaw = $request->fetchAll();

        $appointments = \array_map(
            fn(array $appointmentRaw) => $this->bulkService->makeBulkAppointmentFromDatabase($appointmentRaw),
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
        $appointmentRaw = $request->fetch();

        if (!\is_array($appointmentRaw)) return null;

        /** @phpstan-var BulkAppointmentArray $appointmentRaw */

        $appointment = $this->bulkService->makeBulkAppointmentFromDatabase($appointmentRaw);

        return $appointment;
    }

    /**
     * Crée un RDV vrac.
     * 
     * @param BulkAppointment $appointment RDV à créer
     * 
     * @return BulkAppointment Rendez-vous créé
     */
    public function createAppointment(BulkAppointment $appointment): BulkAppointment
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
            'date_rdv' => $appointment->getSqlDate(),
            'heure' => $appointment->getSqlTime(),
            'produit' => $appointment->getProduct()?->getId(),
            'qualite' => $appointment->getQuality()?->getId(),
            'quantite' => $appointment->getQuantity()?->getValue(),
            'max' => (int) $appointment->getQuantity()?->isMax(),
            'commande_prete' => (int) $appointment->isReady(),
            'fournisseur' => $appointment->getSupplier()?->getId(),
            'client' => $appointment->getCustomer()?->getId(),
            'transporteur' => $appointment->getCarrier()?->getId(),
            'num_commande' => $appointment->getOrderNumber(),
            'commentaire' => $appointment->getComments(),
        ]);

        $lastInsertId = (int) $this->mysql->lastInsertId();
        $this->mysql->commit();

        /** @var BulkAppointment */
        $newAppointment = $this->getAppointment($lastInsertId);

        return $newAppointment;
    }

    /**
     * Met à jour un RDV vrac.
     * 
     * @param BulkAppointment $appointment RDV à modifier
     * 
     * @return BulkAppointment RDV modifié
     */
    public function updateAppointment(BulkAppointment $appointment): BulkAppointment
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
            'date_rdv' => $appointment->getSqlDate(),
            'heure' => $appointment->getSqlTime(),
            'produit' => $appointment->getProduct()?->getId(),
            'qualite' => $appointment->getQuality()?->getId(),
            'quantite' => $appointment->getQuantity()?->getValue(),
            'max' => (int) $appointment->getQuantity()?->isMax(),
            'commande_prete' => (int) $appointment->isReady(),
            'fournisseur' => $appointment->getSupplier()?->getId(),
            'client' => $appointment->getCustomer()?->getId(),
            'transporteur' => $appointment->getCarrier()?->getId(),
            'num_commande' => $appointment->getOrderNumber(),
            'commentaire' => $appointment->getComments(),
            'id' => $appointment->getId(),
        ]);

        /** @var int */
        $id = $appointment->getId();

        /** @var BulkAppointment */
        $updatedAppointment = $this->getAppointment($id);

        return $updatedAppointment;
    }

    /**
     * Met à jour l'état de préparation d'une commande.
     * 
     * @param int  $id     ID du RDV à modifier
     * @param bool $status Statut de la commande
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

        /** @var BulkAppointment */
        $updatedAppointment = $this->getAppointment($id);

        return $updatedAppointment;
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
            "startDate" => $startDate->format('Y-m-d'),
            "endDate" => $endDate->format('Y-m-d'),
        ]);

        /** @phpstan-var BulkAppointmentArray[] $appointmentsRaw */
        $appointmentsRaw = $request->fetchAll();

        $appointments = \array_map(
            fn(array $appointmentRaw) => $this->bulkService->makeBulkAppointmentFromDatabase($appointmentRaw),
            $appointmentsRaw
        );

        return new Collection($appointments);
    }
}
