<?php

// Path: api/src/Repository/PdfConfigRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Config\PdfConfig;
use App\Service\PdfConfigService;

/**
 * @phpstan-import-type PdfConfigArray from \App\Entity\Config\PdfConfig
 */
final class PdfConfigRepository extends Repository
{
    public function __construct(private PdfConfigService $pdfService) {}

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

        /** @var PdfConfigArray[] */
        $configsRaw = $this->mysql->prepareAndExecute($statement)->fetchAll();

        $configs = \array_map(
            fn($config) => $this->pdfService->makeConfigFromDatabase($config),
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

        /** @var ?PdfConfigArray */
        $configRaw = $this->mysql->prepareAndExecute($statement, ['id' => $id])->fetch();

        if (!\is_array($configRaw)) return null;

        $config = $this->pdfService->makeConfigFromDatabase($configRaw);

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

        try {
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute($statement, [
                "module" => $config->module,
                "supplierId" => $config->supplier?->id,
                "autoSend" => (int) $config->autoSend,
                "emails" => $config->getEmailsAsString(),
                "daysBefore" => $config->daysBefore,
                "daysAfter" => $config->daysAfter,
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();
            $this->mysql->commit();
        } catch (\PDOException $e) {
            if ($this->mysql->inTransaction()) {
                $this->mysql->rollBack();
            }

            if ($e->getCode() == 23000) {
                throw new DBException("Une configuration existe déjà pour {$config->module}/{$config->supplier?->shortName}.", previous: $e);
            }

            throw new DBException("Erreur lors de la création", previous: $e);
        }

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

        try {
            $this->mysql->prepareAndExecute($statement, [
                "module" => $config->module,
                "supplierId" => $config->supplier?->id,
                "autoSend" => (int) $config->autoSend,
                "emails" => $config->getEmailsAsString(),
                "daysBefore" => $config->daysBefore,
                "daysAfter" => $config->daysAfter,
                "id" => $config->id,
            ]);
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la mise à jour", previous: $e);
        }

        /** @var int */
        $id = $config->id;

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
        try {
            $this->mysql->prepareAndExecute("DELETE FROM config_pdf WHERE id = :id", ['id' => $id]);
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la suppression", previous: $e);
        }
    }
}
