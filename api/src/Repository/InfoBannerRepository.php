<?php

// Path: api/src/Service/InfoBannerRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Config\InfoBannerLine;
use App\Service\InfoBannerService;

/**
 * @phpstan-type InfoBannerLineArray array{
 *                                     id: int,
 *                                     module: string,
 *                                     pc: bool,
 *                                     tv: bool,
 *                                     message: string,
 *                                     couleur: string,
 *                                   }
 */
final class InfoBannerRepository extends Repository
{
    public function __construct(private InfoBannerService $infoBannerService)
    {
        parent::__construct();
    }

    public function lineExists(int $id): bool
    {
        return $this->mysql->exists("bandeau_info", $id);
    }

    /**
     * Récupère toutes les lignes du bandeau d'infos.
     * 
     * @return Collection<InfoBannerLine> Lignes du bandeau d'infos.
     */
    public function fetchAllLines(): Collection
    {
        $linesRequest = $this->mysql->query("SELECT * FROM bandeau_info");

        if (!$linesRequest) {
            throw new DBException("Impossible de récupérer les lignes du bandeau d'infos.");
        }

        /** @phpstan-var InfoBannerLineArray[] */
        $linesRaw = $linesRequest->fetchAll();

        $lines = \array_map(
            fn($line) => $this->infoBannerService->makeLineFromDatabase($line),
            $linesRaw
        );

        return new Collection($lines);
    }

    /**
     * Récupère une ligne de bandeau d'infos bois.
     * 
     * @param int $id ID de la ligne à récupérer
     * 
     * @return ?InfoBannerLine Ligne récupérée.
     */
    public function fetchLine(int $id): ?InfoBannerLine
    {
        $statement = "SELECT * FROM bandeau_info WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(["id" => $id]);
        $lineRaw = $request->fetch();

        if (!\is_array($lineRaw)) return null;

        /** @phpstan-var InfoBannerLineArray $lineRaw */

        $line = $this->infoBannerService->makeLineFromDatabase($lineRaw);

        return $line;
    }

    /**
     * Crée une ligne du bandeau info.
     * 
     * @param InfoBannerLine $line Eléments de la ligne à créer.
     * 
     * @return InfoBannerLine Ligne créée.
     */
    public function createLine(InfoBannerLine $line): InfoBannerLine
    {
        $statement =
            "INSERT INTO bandeau_info
            SET
                module = :module,
                pc = :pc,
                tv = :tv,
                couleur = :color,
                message = :message";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'module' => $line->getModule(),
            'pc' => (int) $line->isPc(),
            'tv' => (int) $line->isTv(),
            'color' => $line->getColor(),
            'message' => \mb_substr($line->getMessage(), 0, 255),
        ]);

        $lastInsertId = (int) $this->mysql->lastInsertId();
        $this->mysql->commit();

        /** @var InfoBannerLine */
        $newLine = $this->fetchLine($lastInsertId);

        return $newLine;
    }

    /**
     * Met à jour une ligne du bandeau d'informations.
     * 
     * @param InfoBannerLine $line Eléments de la ligne à modifier
     * 
     * @return InfoBannerLine Ligne modifiée
     */
    public function updateLine(InfoBannerLine $line): InfoBannerLine
    {
        $statement =
            "UPDATE bandeau_info
            SET
                module = :module,
                pc = :pc,
                tv = :tv,
                couleur = :color,
                message = :message
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'module' => $line->getModule(),
            'pc' => (int) $line->isPc(),
            'tv' => (int) $line->isTv(),
            'color' => $line->getColor(),
            'message' => \mb_substr($line->getMessage(), 0, 255),
            'id' => $line->getId(),
        ]);

        /** @var int */
        $id = $line->getId();

        /** @var InfoBannerLine */
        $updatedLine = $this->fetchLine($id);

        return $updatedLine;
    }

    /**
     * Supprime une ligne du bandeau d'informations.
     * 
     * @param int $id ID de la ligne à supprimer
     */
    public function deleteLine(int $id): void
    {
        $request = $this->mysql->prepare("DELETE FROM bandeau_info WHERE id = :id");
        $isDeleted = $request->execute(["id" => $id]);

        if (!$isDeleted) {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
