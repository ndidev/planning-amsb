<?php

// Path: api/src/Repository/PdfConfigRepository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Config\PdfConfig;
use App\Service\PdfService;

final class PdfConfigRepository extends Repository
{
    public function configExists(int $id): bool
    {
        return $this->mysql->exists("config_pdf", $id);
    }

    /**
     * Récupère toutes les configurations PDF.
     * 
     * @return Collection<PdfConfig> Toutes les configurations PDF récupérées
     */
    public function fetchAllConfigs(): Collection
    {
        $statement =
            "SELECT
                id,
                module,
                fournisseur,
                envoi_auto,
                liste_emails,
                jours_avant,
                jours_apres
            FROM config_pdf";

        $request = $this->mysql->query($statement);

        if (!$request) {
            throw new DBException("Impossible de récupérer les configurations PDF.");
        }

        $configsRaw = $request->fetchAll();

        $pdfService = new PdfService();

        $configs = array_map(
            fn(array $config) => $pdfService->makeConfigFromDatabase($config),
            $configsRaw
        );

        return new Collection($configs);
    }

    /**
     * Récupère une configuration PDF.
     * 
     * @param int $id ID de la configuration à récupérer.
     * 
     * @return ?PdfConfig Configuration récupérée.
     */
    public function fetchConfig(int $id): ?PdfConfig
    {
        $statement =
            "SELECT
                id,
                module,
                fournisseur,
                envoi_auto,
                liste_emails,
                jours_avant,
                jours_apres
            FROM config_pdf
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);

        $request->execute(['id' => $id]);

        $configRaw = $request->fetch();

        if (!$configRaw) return null;

        $pdfService = new PdfService();

        $config = $pdfService->makeConfigFromDatabase($configRaw);

        return $config;
    }

    /**
     * Crée une configuration PDF.
     * 
     * @param PdfConfig $config Eléments de la configuration à créer.
     * 
     * @return PdfConfig Configuration PDF créée.
     */
    public function createConfig(PdfConfig $config): PdfConfig
    {
        $statement =
            "INSERT INTO config_pdf
            SET
                module = :module,
                fournisseur = :supplierId,
                envoi_auto = :autoSend,
                liste_emails = :emails,
                jours_avant = :daysBefore,
                jours_apres = :daysAfter";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            "module" => $config->getModule(),
            "supplierId" => $config->getSupplier()?->getId(),
            "autoSend" => (int) $config->isAutoSend(),
            "emails" => $config->getEmailsAsString(),
            "daysBefore" => $config->getDaysBefore(),
            "daysAfter" => $config->getDaysAfter(),
        ]);

        $lastInsertId = (int) $this->mysql->lastInsertId();
        $this->mysql->commit();

        /** @var PdfConfig */
        $newConfig = $this->fetchConfig($lastInsertId);

        return $newConfig;
    }

    /**
     * Met à jour une configuration PDF.
     * 
     * @param PdfConfig $config  Eléments de la configuation à modifier.
     * 
     * @return PdfConfig Configuration PDF modifiée.
     */
    public function updateConfig(PdfConfig $config): PdfConfig
    {
        $statement =
            "UPDATE config_pdf
            SET
                module = :module,
                fournisseur = :supplierId,
                envoi_auto = :autoSend,
                liste_emails = :emails,
                jours_avant = :daysBefore,
                jours_apres = :daysAfter
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            "module" => $config->getModule(),
            "supplierId" => $config->getSupplier()?->getId(),
            "autoSend" => (int) $config->isAutoSend(),
            "emails" => $config->getEmailsAsString(),
            "daysBefore" => $config->getDaysBefore(),
            "daysAfter" => $config->getDaysAfter(),
            "id" => $config->getId(),
        ]);

        /** @var int */
        $id = $config->getId();

        /** @var PdfConfig */
        $updatedConfig = $this->fetchConfig($id);

        return $updatedConfig;
    }

    /**
     * Supprime une configuration PDF.
     * 
     * @param int $id ID de la configuration à supprimer.
     * 
     * @throws DBException
     */
    public function deleteConfig(int $id): void
    {
        $request = $this->mysql->prepare("DELETE FROM config_pdf WHERE id = :id");
        $isDeleted = $request->execute(["id" => $id]);

        if (!$isDeleted) {
            throw new DBException("Erreur lors de la suppression");
        };
    }
}
