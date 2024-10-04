<?php

// Path: api/src/Repository/QuickAppointmentAddRepository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Component\Module;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Config\QuickAppointmentAdd;
use App\Entity\Config\TimberQuickAppointmentAdd;
use App\Service\QuickAppointmentAddService;

class QuickAppointmentAddRepository extends Repository
{
    public function quickAddExists(Module $module, int $id): bool
    {
        return match ($module) {
            Module::TIMBER => $this->mysql->exists("config_ajouts_rapides_bois", $id),
            default => false,
        };
    }

    /**
     * Récupère tous les ajouts rapides.
     * 
     * @return array<string, Collection<QuickAppointmentAdd>> Ajouts rapides récupérés.
     */
    public function fetchAllQuickAppointmentAdds(): array
    {
        return [
            Module::TIMBER->value => $this->fetchAllTimberQuickAppointmentAdds()
        ];
    }

    /**
     * Récupère tous les ajouts rapides bois.
     * 
     * @return Collection<QuickAppointmentAdd> Ajouts rapides récupérés.
     */
    public function fetchAllTimberQuickAppointmentAdds(): Collection
    {
        $statement = "SELECT * FROM config_ajouts_rapides_bois";

        $quickAddConfigsRaw = $this->mysql->query($statement)->fetchAll();

        $quickAppointmentAddService = new QuickAppointmentAddService();

        $quickAddConfigs = array_map(
            fn(array $quickAddRaw) => $quickAppointmentAddService->makeTimberQuickAppointmentAddFromDatabase($quickAddRaw),
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

        if (!$quickAddConfigRaw) return null;

        $quickAppointmentAddService = new QuickAppointmentAddService();

        $quickAddConfig = $quickAppointmentAddService->makeTimberQuickAppointmentAddFromDatabase($quickAddConfigRaw);

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
            'module' => $quickAdd->getModule()->value,
            'supplierId' => $quickAdd->getSupplier()?->getId(),
            'carrierId' => $quickAdd->getCarrier()?->getId(),
            'chartererId' => $quickAdd->getCharterer()?->getId(),
            'loadingId' => $quickAdd->getLoading()?->getId(),
            'customerId' => $quickAdd->getCustomer()?->getId(),
            'deliveryId' => $quickAdd->getDelivery()?->getId(),
        ]);

        $lastInsertId = (int) $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->fetchImtberQuickAppointmentAdd($lastInsertId);
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
            'supplierId' => $quickAdd->getSupplier()?->getId(),
            'carrierId' => $quickAdd->getCarrier()?->getId(),
            'chartererId' => $quickAdd->getCharterer()?->getId(),
            'loadingId' => $quickAdd->getLoading()?->getId(),
            'customerId' => $quickAdd->getCustomer()?->getId(),
            'deliveryId' => $quickAdd->getDelivery()?->getId(),
            'id' => $quickAdd->getId(),
        ]);

        return $this->fetchImtberQuickAppointmentAdd($quickAdd->getId());
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
