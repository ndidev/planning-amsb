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
 * @phpstan-type TimberQuickAppointmentAddArray array{
 *                                                id: int|null,
 *                                                fournisseur: int,
 *                                                transporteur: int,
 *                                                affreteur: int,
 *                                                chargement: int,
 *                                                client: int,
 *                                                livraison: int,
 *                                              }
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

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'module' => $quickAdd->getModule(),
            'supplierId' => $quickAdd->getSupplier()?->id,
            'carrierId' => $quickAdd->getCarrier()?->id,
            'chartererId' => $quickAdd->getCharterer()?->id,
            'loadingId' => $quickAdd->getLoading()?->id,
            'customerId' => $quickAdd->getCustomer()?->id,
            'deliveryId' => $quickAdd->getDelivery()?->id,
        ]);

        $lastInsertId = (int) $this->mysql->lastInsertId();
        $this->mysql->commit();

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

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'supplierId' => $quickAdd->getSupplier()?->id,
            'carrierId' => $quickAdd->getCarrier()?->id,
            'chartererId' => $quickAdd->getCharterer()?->id,
            'loadingId' => $quickAdd->getLoading()?->id,
            'customerId' => $quickAdd->getCustomer()?->id,
            'deliveryId' => $quickAdd->getDelivery()?->id,
            'id' => $quickAdd->id,
        ]);

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
        $request = $this->mysql->prepare("DELETE FROM config_ajouts_rapides_bois WHERE id = :id");
        $isDeleted = $request->execute(["id" => $id]);

        if (!$isDeleted) {
            throw new DBException("Erreur lors de la suppression");
        };
    }
}
