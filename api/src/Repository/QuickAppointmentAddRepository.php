<?php

// Path: api/src/Repository/QuickAppointmentAddRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Component\Module;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Config\TimberQuickAppointmentAdd;
use App\Service\QuickAppointmentAddService;

/**
 * @phpstan-import-type TimberQuickAppointmentAddArray from \App\Entity\Config\TimberQuickAppointmentAdd
 */
final class QuickAppointmentAddRepository extends Repository
{
    public function __construct(private QuickAppointmentAddService $quickAppointmentAddService) {}

    public function quickAddExists(string $module, int $id): bool
    {
        return match ($module) {
            Module::TIMBER => $this->mysql->exists("config_ajouts_rapides_bois", $id),
            default => false,
        };
    }

    /**
     * Récupère tous les ajouts rapides.
     * 
     * @return array{bois: Collection<TimberQuickAppointmentAdd>} Ajouts rapides récupérés.
     */
    public function fetchAllQuickAppointmentAdds(): array
    {
        return [
            Module::TIMBER => $this->fetchAllTimberQuickAppointmentAdds(),
        ];
    }

    /**
     * Récupère tous les ajouts rapides bois.
     * 
     * @return Collection<TimberQuickAppointmentAdd> Ajouts rapides récupérés.
     */
    public function fetchAllTimberQuickAppointmentAdds(): Collection
    {
        $statement = "SELECT * FROM config_ajouts_rapides_bois";

        $quickAddConfigsRequest = $this->mysql->query($statement);

        if (!$quickAddConfigsRequest) {
            throw new DBException("Impossible de récupérer les ajouts rapides bois.");
        }

        /** @phpstan-var TimberQuickAppointmentAddArray[] $quickAddConfigsRaw */
        $quickAddConfigsRaw = $quickAddConfigsRequest->fetchAll();

        $quickAddConfigs = \array_map(
            fn(array $quickAddRaw) => $this->quickAppointmentAddService->makeTimberQuickAppointmentAddFromDatabase($quickAddRaw),
            $quickAddConfigsRaw
        );

        return new Collection($quickAddConfigs);
    }

    /**
     * Récupère un ajout rapide bois.
     * 
     * @param int $id ID de l'ajout rapide à récupérer
     * 
     * @return ?TimberQuickAppointmentAdd Rendez-vous rapide récupéré
     */
    public function fetchImtberQuickAppointmentAdd(int $id): ?TimberQuickAppointmentAdd
    {
        $statement = "SELECT * FROM config_ajouts_rapides_bois WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(["id" => $id]);

        $quickAddConfigRaw = $request->fetch();

        if (!\is_array($quickAddConfigRaw)) return null;

        /** @phpstan-var TimberQuickAppointmentAddArray $quickAddConfigRaw */

        $quickAddConfig = $this->quickAppointmentAddService->makeTimberQuickAppointmentAddFromDatabase($quickAddConfigRaw);

        return $quickAddConfig;
    }

    /**
     * Crée un ajout rapide bois.
     * 
     * @param TimberQuickAppointmentAdd $quickAdd Eléments de l'ajout rapide à créer.
     * 
     * @return TimberQuickAppointmentAdd Rendez-vous rapide créé.
     */
    public function createTimberQuickAppointmentAdd(TimberQuickAppointmentAdd $quickAdd): TimberQuickAppointmentAdd
    {
        $statement =
            "INSERT INTO config_ajouts_rapides_bois
            SET
                module = :module,
                fournisseur = :supplierId,
                transporteur = :carrierId,
                affreteur = :chartererId,
                chargement = :loadingId,
                client = :customerId,
                livraison = :deliveryId
            ";
        try {
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute($statement, [
                'module' => $quickAdd->module,
                'supplierId' => $quickAdd->supplier?->id,
                'carrierId' => $quickAdd->carrier?->id,
                'chartererId' => $quickAdd->charterer?->id,
                'loadingId' => $quickAdd->loading?->id,
                'customerId' => $quickAdd->customer?->id,
                'deliveryId' => $quickAdd->delivery?->id,
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();
            $this->mysql->commit();
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la création de l'ajout rapide bois.", previous: $e);
        }

        /** @var TimberQuickAppointmentAdd */
        $newTimberQuickAppointmentAdd = $this->fetchImtberQuickAppointmentAdd($lastInsertId);

        return $newTimberQuickAppointmentAdd;
    }

    /**
     * Met à jour un ajout rapide bois.
     * 
     * @param TimberQuickAppointmentAdd $quickAdd Eléments de l'ajout rapide à modifier
     * 
     * @return TimberQuickAppointmentAdd Ajout rapide modifié
     */
    public function updateTimberQuickAppointmentAdd(TimberQuickAppointmentAdd $quickAdd): TimberQuickAppointmentAdd
    {
        $statement =
            "UPDATE config_ajouts_rapides_bois
            SET
                fournisseur = :supplierId,
                transporteur = :carrierId,
                affreteur = :chartererId,
                chargement = :loadingId,
                client = :customerId,
                livraison = :deliveryId
            WHERE id = :id";

        try {
            $this->mysql->prepareAndExecute($statement, [
                'supplierId' => $quickAdd->supplier?->id,
                'carrierId' => $quickAdd->carrier?->id,
                'chartererId' => $quickAdd->charterer?->id,
                'loadingId' => $quickAdd->loading?->id,
                'customerId' => $quickAdd->customer?->id,
                'deliveryId' => $quickAdd->delivery?->id,
                'id' => $quickAdd->id,
            ]);
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la mise à jour de l'ajout rapide bois.", previous: $e);
        }

        /** @var int */
        $id = $quickAdd->id;

        /** @var TimberQuickAppointmentAdd */
        $updatedQuickAdd = $this->fetchImtberQuickAppointmentAdd($id);

        return $updatedQuickAdd;
    }

    /**
     * Supprime un ajout rapide bois.
     * 
     * @param int $id ID de l'ajout rapide à supprimer.
     * 
     * @throws DBException Erreur lors de la suppression.
     */
    public function deleteTimberQuickAppointmentAdd(int $id): void
    {
        try {
            $this->mysql->prepareAndExecute("DELETE FROM config_ajouts_rapides_bois WHERE id = :id", ['id' => $id]);
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la suppression de l'ajout rapide bois.", previous: $e);
        }
    }
}
