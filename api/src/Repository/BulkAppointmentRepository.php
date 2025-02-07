<?php

// Path: api/src/Repository/BulkAppointmentRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\BulkDispatchStatsDTO;
use App\DTO\Filter\BulkDispatchStatsFilterDTO;
use App\DTO\Filter\BulkFilterDTO;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkDispatchItem;
use App\Entity\ThirdParty;
use App\Service\BulkService;

/**
 * @phpstan-import-type BulkAppointmentArray from \App\Entity\Bulk\BulkAppointment
 * @phpstan-import-type BulkDispatchArray from \App\Entity\Bulk\BulkDispatchItem
 */
final class BulkAppointmentRepository extends Repository
{
    public function __construct(private BulkService $bulkService) {}

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function appointmentExists(int $id): bool
    {
        return $this->mysql->exists('vrac_planning', $id);
    }

    /**
     * Fetch all the bulk appointments.
     * 
     * @param BulkFilterDTO $filter The filter to apply.
     * 
     * @return Collection<BulkAppointment> The fetched appointments.
     */
    public function getAppointments(BulkFilterDTO $filter): Collection
    {
        $sqlFilter = $filter->getSqlFilter();

        $archiveFilter = (int) $filter->isArchive();

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
                commentaire_public,
                commentaire_prive,
                show_on_tv,
                archive
            FROM vrac_planning
            WHERE
                date_rdv BETWEEN :startDate AND :endDate
                AND archive = $archiveFilter
                $sqlFilter
            ORDER BY date_rdv";

        try {
            /** @phpstan-var BulkAppointmentArray[] $appointmentsRaw */
            $appointmentsRaw = $this->mysql
                ->prepareAndExecute(
                    $statement,
                    [
                        'startDate' => $filter->getSqlStartDate(),
                        'endDate' => $filter->getSqlEndDate(),
                    ]
                )
                ->fetchAll();

            $appointments = \array_map(
                fn(array $appointmentRaw) => $this->bulkService->makeBulkAppointmentFromDatabase($appointmentRaw),
                $appointmentsRaw
            );

            // Get all dispatch
            foreach ($appointments as $appointment) {
                /** @var int $id */
                $id = $appointment->id;
                $dispatch = $this->fetchDispatchForAppointment($id);
                $appointment->setDispatch($dispatch);
            }

            return new Collection($appointments);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les RDV vrac.", previous: $e);
        }
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
                commentaire_public,
                commentaire_prive,
                show_on_tv,
                archive
            FROM vrac_planning
            WHERE id = :id";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de récupérer le RDV vrac {$id}");
            }

            $request->execute(["id" => $id]);
            $appointmentRaw = $request->fetch();

            if (!\is_array($appointmentRaw)) return null;

            /** @phpstan-var BulkAppointmentArray $appointmentRaw */

            $appointment = $this->bulkService->makeBulkAppointmentFromDatabase($appointmentRaw);

            // Get dispatch
            $dispatch = $this->fetchDispatchForAppointment($id);
            $appointment->setDispatch($dispatch);

            return $appointment;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer le RDV vrac {$id}", previous: $e);
        }
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
                date_rdv = :date,
                heure = :time,
                produit = :productId,
                qualite = :qualityId,
                quantite = :quantity,
                max = :max,
                commande_prete = :orderIsReady,
                fournisseur = :supplierId,
                client = :customerId,
                transporteur = :carrierId,
                num_commande = :orderNumber,
                commentaire_public = :publicComments,
                commentaire_prive = :privateComments,
                show_on_tv = :showOnTv,
                archive = :archive
                ";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de créer le RDV vrac.");
            }

            $this->mysql->beginTransaction();
            $request->execute([
                'date' => $appointment->getSqlDate(),
                'time' => $appointment->getSqlTime(),
                'productId' => $appointment->getProduct()?->id,
                'qualityId' => $appointment->getQuality()?->id,
                'quantity' => $appointment->getQuantityValue(),
                'max' => (int) $appointment->getQuantityIsMax(),
                'orderIsReady' => (int) $appointment->isReady(),
                'supplierId' => $appointment->getSupplier()?->id,
                'customerId' => $appointment->getCustomer()?->id,
                'carrierId' => $appointment->getCarrier()?->id,
                'orderNumber' => $appointment->getOrderNumber(),
                'publicComments' => $appointment->getPublicComments(),
                'privateComments' => $appointment->getPrivateComments(),
                'showOnTv' => (int) $appointment->isOnTv(),
                'archive' => (int) $appointment->isArchive(),
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            $this->insertDispatchForAppointment($lastInsertId, $appointment->getDispatch());

            $this->mysql->commit();

            /** @var BulkAppointment */
            $newAppointment = $this->getAppointment($lastInsertId);

            return $newAppointment;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de créer le RDV vrac.", previous: $e);
        }
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
                date_rdv = :date,
                heure = :time,
                produit = :productId,
                qualite = :qualityId,
                quantite = :quantity,
                max = :max,
                commande_prete = :orderIsReady,
                fournisseur = :supplierId,
                client = :customerId,
                transporteur = :carrierId,
                num_commande = :orderNumber,
                commentaire_public = :publicComments,
                commentaire_prive = :privateComments,
                show_on_tv = :showOnTv,
                archive = :archive
            WHERE id = :id";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de mettre à jour le RDV vrac.");
            }

            $request->execute([
                'date' => $appointment->getSqlDate(),
                'time' => $appointment->getSqlTime(),
                'productId' => $appointment->getProduct()?->id,
                'qualityId' => $appointment->getQuality()?->id,
                'quantity' => $appointment->getQuantityValue(),
                'max' => (int) $appointment->getQuantityIsMax(),
                'orderIsReady' => (int) $appointment->isReady(),
                'supplierId' => $appointment->getSupplier()?->id,
                'customerId' => $appointment->getCustomer()?->id,
                'carrierId' => $appointment->getCarrier()?->id,
                'orderNumber' => $appointment->getOrderNumber(),
                'publicComments' => $appointment->getPublicComments(),
                'privateComments' => $appointment->getPrivateComments(),
                'showOnTv' => (int) $appointment->isOnTv(),
                'archive' => (int) $appointment->isArchive(),
                'id' => $appointment->id,
            ]);


            /** @var int */
            $id = $appointment->id;

            $this->deleteDispatchForAppointment($id);
            $this->insertDispatchForAppointment($id, $appointment->getDispatch());

            /** @var BulkAppointment */
            $updatedAppointment = $this->getAppointment($id);

            return $updatedAppointment;
        } catch (\Throwable $th) {
            throw new DBException("Impossible de mettre à jour le RDV vrac.", previous: $th);
        }
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
                SET commande_prete = :orderIsReady
                WHERE id = :id"
            )
            ->execute([
                'orderIsReady' => (int) $status,
                'id' => $id,
            ]);

        /** @var BulkAppointment */
        $updatedAppointment = $this->getAppointment($id);

        return $updatedAppointment;
    }

    /**
     * Met à jour l'état d'archivage d'un RDV.
     * 
     * @param int $id 
     * @param bool $status 
     * 
     * @return BulkAppointment 
     */
    public function setIsArchive(int $id, bool $status): BulkAppointment
    {
        $this->mysql
            ->prepare(
                "UPDATE vrac_planning
                SET archive = :archive
                WHERE id = :id"
            )
            ->execute([
                'archive' => (int) $status,
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
        static $request;

        try {
            if (!$request instanceof \PDOStatement) {
                $request = $this->mysql->prepare("DELETE FROM vrac_planning WHERE id = :id");
            }

            if (!$request) {
                throw new DBException("Impossible de supprimer le RDV vrac.");
            }

            $isDeleted = $request->execute(["id" => $id]);

            if (!$isDeleted) {
                throw new DBException("Impossible de supprimer le RDV vrac.");
            }
        } catch (\PDOException $e) {
            throw new DBException("Impossible de supprimer le RDV vrac.", previous: $e);
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

        static $request;

        try {
            if (!$request instanceof \PDOStatement) {
                $request = $this->mysql->prepare($statement);
            }

            if (!$request) {
                throw new DBException("Impossible de récupérer les RDV vrac à exporter.");
            }

            $request->execute([
                "supplierId" => $supplier->id,
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
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les RDV vrac à exporter.", previous: $e);
        }
    }

    // ========
    // Dispatch
    // ========

    /**
     * Fetch the dispatch for an appointment.
     * 
     * @param int $id Appointment ID.
     * 
     * @return BulkDispatchItem[]
     * 
     * @throws DBException
     */
    public function fetchDispatchForAppointment(int $id): array
    {
        $statement =
            "SELECT
                staff_id,
                `date`,
                remarks
            FROM stevedoring_bulk_dispatch
            WHERE appointment_id = :id";

        try {
            /** @phpstan-var BulkDispatchArray[] */
            $dispatchesRaw = $this->mysql
                ->prepareAndExecute($statement, ["id" => $id])
                ->fetchAll();

            $dispatches = \array_map(
                fn(array $dispatchRaw) => $this->bulkService->makeBulkDispatchItemFromDatabase($dispatchRaw),
                $dispatchesRaw
            );

            return $dispatches;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer le dispatch pour le RDV {$id}", previous: $e);
        }
    }

    /**
     * Insert the dispatch for an appointment.
     * 
     * @param int $id Appointment ID.
     * @param BulkDispatchItem[] $dispatch 
     * 
     * @return void 
     * 
     * @throws DBException 
     */
    public function insertDispatchForAppointment(int $id, array $dispatch): void
    {
        if (\count($dispatch) === 0) return;

        $statement =
            "INSERT INTO stevedoring_bulk_dispatch
             SET
                appointment_id = :id,
                staff_id = :staffId,
                `date` = :date,
                remarks = :remarks";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                \array_map(
                    fn(BulkDispatchItem $dispatchItem) => [
                        'id' => $id,
                        'staffId' => $dispatchItem->getStaff()?->id,
                        'date' => $dispatchItem->getDate()?->format('Y-m-d'),
                        'remarks' => $dispatchItem->getRemarks(),
                    ],
                    $dispatch
                )
            );
        } catch (\PDOException $e) {
            throw new DBException("Impossible d'enregistrer le dispatch", previous: $e);
        }
    }

    /**
     * Delete the dispatch for an appointment.
     * 
     * @param int $id Appointment ID.
     * 
     * @return void 
     * 
     * @throws DBException 
     */
    public function deleteDispatchForAppointment(int $id): void
    {
        try {
            $this->mysql->prepareAndExecute(
                "DELETE FROM stevedoring_bulk_dispatch WHERE appointment_id = :id",
                ["id" => $id]
            );
        } catch (\PDOException $e) {
            throw new DBException("Impossible de supprimer le dispatch du RDV {$id}", previous: $e);
        }
    }

    /**
     * Insert the dispatch for an appointment.
     * 
     * @param int $id Appointment ID.
     * @param BulkDispatchItem[] $dispatch 
     * 
     * @return BulkAppointment Updated appointment.
     * 
     * @throws DBException 
     */
    public function updateDispatchForAppointment(int $id, array $dispatch): BulkAppointment
    {
        $this->deleteDispatchForAppointment($id);
        $this->insertDispatchForAppointment($id, $dispatch);

        /** @var BulkAppointment */
        $updatedAppointment = $this->getAppointment($id);

        return $updatedAppointment;
    }

    public function fetchDispatchStats(BulkDispatchStatsFilterDTO $filter): BulkDispatchStatsDTO
    {
        $sqlFilter = $filter->getSqlFilter();

        $dispatchStatement =
            "SELECT
                dispatch.date,
                IF(
                    staff.type = 'interim',
                    'Intérimaires',
                    CONCAT(staff.lastname, ' ', staff.firstname)
                ) as `staffLabel`,
                dispatch.remarks as `remarks`,
                p.unite as `unit`
            FROM stevedoring_bulk_dispatch dispatch
            INNER JOIN vrac_planning pl ON pl.id = dispatch.appointment_id
            INNER JOIN vrac_produits p ON pl.produit = p.id
            INNER JOIN stevedoring_staff staff ON dispatch.staff_id = staff.id
            WHERE dispatch.date BETWEEN :startDate AND :endDate
                AND p.unite IN ('T', 'BB')
                $sqlFilter
            ORDER BY
                dispatch.date ASC,
                staff.lastname ASC,
                staff.firstname ASC
            ";

        static $dispatchRequest;

        try {
            if (!$dispatchRequest instanceof \PDOStatement) {
                $dispatchRequest = $this->mysql->prepare($dispatchStatement);
            }

            if (!$dispatchRequest) {
                throw new DBException("Impossible de récupérer les statistiques de dispatch");
            }

            $dispatchRequest->execute([
                "startDate" => $filter->getSqlStartDate(),
                "endDate" => $filter->getSqlEndDate(),
            ]);

            /** 
             * @var array{
             *        date: string,
             *        staffLabel: string,
             *        remarks: string,
             *        unit: string,
             *      }[]
             */
            $rawData = $dispatchRequest->fetchAll();

            $dispatchDTO = new BulkDispatchStatsDTO($rawData);

            return $dispatchDTO;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les statistiques de dispatch", previous: $e);
        }
    }
}
