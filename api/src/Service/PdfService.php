<?php

// Path: api/src/Service/PdfConfigService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Config\PdfConfig;
use App\Repository\PdfConfigRepository;

class PdfService
{
    private PdfConfigRepository $pdfConfigRepository;

    public function __construct()
    {
        $this->pdfConfigRepository = new PdfConfigRepository();
    }

    public function makeConfigFromDatabase(array $rawData): PdfConfig
    {
        $config = (new PdfConfig())
            ->setId($rawData['id'] ?? null)
            ->setModule($rawData['module'] ?? null)
            ->setSupplier($rawData['fournisseur'] ?? null)
            ->setAutoSend($rawData['envoi_auto'] ?? false)
            ->setEmails($rawData['liste_emails'] ?? [])
            ->setDaysBefore($rawData['jours_avant'] ?? 0)
            ->setDaysAfter($rawData['jours_apres'] ?? 0);

        return $config;
    }

    public function makeConfigFromForm(array $rawData): PdfConfig
    {
        $config = (new PdfConfig())
            ->setModule($rawData['module'] ?? null)
            ->setSupplier($rawData['fournisseur'] ?? null)
            ->setAutoSend($rawData['envoi_auto'] ?? false)
            ->setEmails($rawData['liste_emails'] ?? [])
            ->setDaysBefore($rawData['jours_avant'] ?? 0)
            ->setDaysAfter($rawData['jours_apres'] ?? 0);

        return $config;
    }

    public function configExists(int $id): bool
    {
        return $this->pdfConfigRepository->configExists($id);
    }

    public function getAllConfigs(): Collection
    {
        return $this->pdfConfigRepository->fetchAllConfigs();
    }

    public function getConfig(int $id): ?PdfConfig
    {
        return $this->pdfConfigRepository->fetchConfig($id);
    }

    public function updateConfig(int $id, array $rawData): PdfConfig
    {
        $config = $this->makeConfigFromForm($rawData)->setId($id);

        return $this->pdfConfigRepository->updateConfig($config);
    }

    public function createConfig(array $rawData): PdfConfig
    {
        $config = $this->makeConfigFromForm($rawData);

        return $this->pdfConfigRepository->createConfig($config);
    }

    public function deleteConfig(int $id): void
    {
        $this->pdfConfigRepository->deleteConfig($id);
    }
}
