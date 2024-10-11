<?php

// Path: api/src/Service/InfoBannerRepository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Config\InfoBannerLine;
use App\Service\InfoBannerService;

class InfoBannerRepository extends Repository
{
    public function lineExists(int $id): bool
    {
        return $this->mysql->exists("bandeau_info", $id);
    }

    /**
     * Récupère toutes les lignes du bandeau d'infos.
     * 
     * @return Collection<InfoBannerLine> Lignes du bandeau d'infos.
     */
    public function fetchAllLines(array $filter): Collection
    {
        // Filtre
        $module = $filter['module'] ?? "";
        $pc = $filter['pc'] ?? "";
        $tv = $filter['tv'] ?? "";

        $sqlFilter = "";
        $sqlModuleFilter = $module === "" ? "" : "module = '$module'";
        $sqlPcFilter = $pc === "" ? "" : "pc = $pc";
        $sqlTvFilter = $tv === "" ? "" : "tv = $tv";

        $sqlFilterArray = [];
        foreach ([$sqlModuleFilter, $sqlPcFilter, $sqlTvFilter] as $filterPart) {
            if ($filterPart !== "") {
                array_push($sqlFilterArray, $filterPart);
            }
        }
        if ($sqlFilterArray !== []) {
            $sqlFilter = "WHERE " . join(" AND ", $sqlFilterArray);
        }

        $statement = "SELECT * FROM bandeau_info $sqlFilter";

        $request = $this->mysql->query($statement);
        $linesRaw = $request->fetchAll();

        $infoBannerService = new InfoBannerService();

        $lines = array_map(fn(array $line) => $infoBannerService->makeLineFromDatabase($line), $linesRaw);

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

        if (!$lineRaw) return null;

        $infoBannerService = new InfoBannerService();

        $line = $infoBannerService->makeLineFromDatabase($lineRaw);

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
            'module' => $line->getModule()->value,
            'pc' => (int) $line->isPc(),
            'tv' => (int) $line->isTv(),
            'color' => $line->getColor(),
            'message' => mb_substr($line->getMessage(), 0, 255),
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->fetchLine($lastInsertId);
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
            'module' => $line->getModule()->value,
            'pc' => (int) $line->isPc(),
            'tv' => (int) $line->isTv(),
            'color' => $line->getColor(),
            'message' => mb_substr($line->getMessage(), 0, 255),
            'id' => $line->getId(),
        ]);

        return $this->fetchLine($line->getId());
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
