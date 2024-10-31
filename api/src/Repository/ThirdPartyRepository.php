<?php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\ThirdParty;
use App\Service\ThirdPartyService;

final class ThirdPartyRepository extends Repository
{
    /**
     * @var ThirdParty[]
     */
    static private array $cache = [];

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function thirdPartyExists(int $id): bool
    {
        return $this->mysql->exists("tiers", $id);
    }

    /**
     * Récupère tous les tiers.
     * 
     * @return Collection<ThirdParty> Liste des tiers
     */
    public function fetchAllThirdParties(): Collection
    {
        $statement = "SELECT * FROM tiers ORDER BY nom_court, ville";

        $thirdPartiesRaw = $this->mysql->query($statement)->fetchAll();

        $thirdPartyService = new ThirdPartyService();

        $thirdParties = array_map(
            fn(array $thirdPartyRaw) => $thirdPartyService->makeThirdPartyFromDatabase($thirdPartyRaw),
            $thirdPartiesRaw
        );

        static::$cache = $thirdParties;

        return new Collection($thirdParties);
    }

    /**
     * Récupère un tiers.
     * 
     * @param int $id ID du tiers à récupérer
     * 
     * @return ?ThirdParty Tiers récupéré
     */
    public function fetchThirdParty(int $id): ?ThirdParty
    {
        // Vérifier si le tiers est déjà en cache
        $thirdParty = array_filter(
            static::$cache,
            fn(ThirdParty $cachedThirdParty) => $cachedThirdParty->getId() === $id
        )[0] ?? null;

        if ($thirdParty) {
            return $thirdParty;
        }

        $statement = "SELECT * FROM tiers WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(["id" => $id]);
        $thirdPartyRaw = $request->fetch();

        if (!$thirdPartyRaw) return null;

        $thirdPartyService = new ThirdPartyService();

        $thirdParty = $thirdPartyService->makeThirdPartyFromDatabase($thirdPartyRaw);

        array_push(static::$cache, $thirdParty);

        return $thirdParty;
    }

    /**
     * Crée un tiers.
     * 
     * @param ThirdParty $thirdParty Eléments du tiers à créer
     * 
     * @return ThirdParty Tiers créé
     */
    public function createThirdParty(ThirdParty $thirdParty): ThirdParty
    {
        $statement =
            "INSERT INTO tiers
            SET
                nom_court = :shortName,
                nom_complet = :fullName,
                adresse_ligne_1 = :addressLine1,
                adresse_ligne_2 = :addressLine2,
                cp = :postCode,
                ville = :city,
                pays = :country,
                telephone = :phone,
                commentaire = :comments,
                roles = := roles,
                logo = :logo,
                actif = :active";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'shortName' => $thirdParty->getShortName() ?: $thirdParty->getFullName(),
            'fullName' => $thirdParty->getFullName(),
            'addressLine1' => $thirdParty->getAddressLine1(),
            'addressLine2' => $thirdParty->getAddressLine2(),
            'postCode' => $thirdParty->getPostCode(),
            'city' => $thirdParty->getCity(),
            'country' => $thirdParty->getCountry()->getISO(),
            'phone' => $thirdParty->getPhone(),
            'comments' => $thirdParty->getComments(),
            'roles' => json_encode($thirdParty->getRoles()),
            'logo' => $thirdParty->getLogoFilename(),
            'active' => (int) $thirdParty->isActive(),
        ]);

        $lastInsertId = (int) $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->fetchThirdParty($lastInsertId);
    }

    /**
     * Met à jour un tiers.
     * 
     * @param ThirdParty $thirdParty  Eléments du tiers à modifier
     * 
     * @return ThirdParty tiers modifié
     */
    public function updateThirdParty(ThirdParty $thirdParty): ThirdParty
    {
        // Si un logo a été ajouté, l'utiliser, sinon, ne pas changer
        $logoStatement = $thirdParty->getLogoFilename() !== false ? ":logo" : "logo";

        $thirdPartyStatement =
            "UPDATE tiers
            SET
                nom_court = :shortName,
                nom_complet = :fullName,
                adresse_ligne_1 = :addressLine1,
                adresse_ligne_2 = :addressLine2,
                cp = :postCode,
                ville = :city,
                pays = :country,
                telephone = :phone,
                commentaire = :comments,
                roles = :roles,
                logo = $logoStatement,
                actif = :active
            WHERE id = :id";

        $request = $this->mysql->prepare($thirdPartyStatement);

        $fields = [
            'shortName' => $thirdParty->getShortName() ?: $thirdParty->getFullName(),
            'fullName' => $thirdParty->getFullName(),
            'addressLine1' => $thirdParty->getAddressLine1(),
            'addressLine2' => $thirdParty->getAddressLine2(),
            'postCode' => $thirdParty->getPostCode(),
            'city' => $thirdParty->getCity(),
            'country' => $thirdParty->getCountry()->getISO(),
            'phone' => $thirdParty->getPhone(),
            'comments' => $thirdParty->getComments(),
            'roles' => json_encode($thirdParty->getRoles()),
            'active' => (int) $thirdParty->isActive(),
            'id' => $thirdParty->getId(),
        ];

        if ($thirdParty->getLogoFilename() !== false) {
            $fields["logo"] = $thirdParty->getLogoFilename();
        }

        $request->execute($fields);

        return $this->fetchThirdParty($thirdParty->getId());
    }

    /**
     * Supprime un tiers.
     * 
     * @param int $id ID du tiers à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteThirdParty(int $id): bool
    {
        $appointmentCount = $this->getAppointmentCountForId($id);
        if ($appointmentCount > 0) {
            throw new ClientException("Le tiers est concerné par {$appointmentCount} rdv. Impossible de le supprimer.");
        }

        $deleteRequest = $this->mysql->prepare("DELETE FROM tiers WHERE id = :id");
        $isDeleted = $deleteRequest->execute(["id" => $id]);

        if (!$isDeleted) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $isDeleted;
    }

    /**
     * Récupère le nombre de RDV pour tous les tiers.
     * 
     * @return array<string, int> Nombre de RDV pour le(s) tiers, indexé par ID.
     */
    public function getAllAppointmentCounts(): array
    {
        $statement =
            "SELECT 
                t.id,
                (
                (SELECT COUNT(v.id)
                    FROM vrac_planning v
                    WHERE t.id IN (
                    v.client,
                    v.transporteur,
                    v.fournisseur
                    )
                )
                +
                (SELECT COUNT(b.id)
                    FROM bois_planning b
                    WHERE t.id IN (
                    b.client,
                    b.chargement,
                    b.livraison,
                    b.transporteur,
                    b.affreteur,
                    b.fournisseur
                    )
                )
                +
                (SELECT COUNT(c.id)
                    FROM consignation_planning c
                    WHERE t.id IN (
                    c.armateur
                    )
                )
                +
                (SELECT COUNT(ch.id)
                    FROM chartering_registre ch
                    WHERE t.id IN (
                    ch.armateur,
                    ch.affreteur,
                    ch.courtier
                    )
                )
                ) AS nombre_rdv
            FROM tiers t
            ";

        $request = $this->mysql->query($statement);
        /** @var list<array{id: int, nombre_rdv: int}> $appointmentCountWithoutKeys */
        $appointmentCountWithoutKeys = $request->fetchAll();

        /** @var array<string, int> $appointmentCountWithKeys */
        $appointmentCountWithKeys = [];
        foreach ($appointmentCountWithoutKeys as $appointmentCount) {
            if ($appointmentCount["nombre_rdv"] > 0) {
                $appointmentCountWithKeys[(string) $appointmentCount["id"]] = $appointmentCount["nombre_rdv"];
            }
        }

        return $appointmentCountWithKeys;
    }

    /**
     * Récupère le nombre de RDV pour un tiers.
     * 
     * @param int $id Optionnel. ID du tiers à récupérer.
     * 
     * @return int|false 
     */
    public function getAppointmentCountForId(int $id): int|false
    {
        $statement =
            "SELECT 
                (
                (SELECT COUNT(v.id)
                    FROM vrac_planning v
                    WHERE t.id IN (
                    v.client,
                    v.transporteur,
                    v.fournisseur
                    )
                )
                +
                (SELECT COUNT(b.id)
                    FROM bois_planning b
                    WHERE t.id IN (
                    b.client,
                    b.chargement,
                    b.livraison,
                    b.transporteur,
                    b.affreteur,
                    b.fournisseur
                    )
                )
                +
                (SELECT COUNT(c.id)
                    FROM consignation_planning c
                    WHERE t.id IN (
                    c.armateur
                    )
                )
                +
                (SELECT COUNT(ch.id)
                    FROM chartering_registre ch
                    WHERE t.id IN (
                    ch.armateur,
                    ch.affreteur,
                    ch.courtier
                    )
                )
                ) AS nombre_rdv
            FROM tiers t
            WHERE t.id = :id
            ";

        $request = $this->mysql->prepare($statement);
        $request->execute(["id" => $id]);
        /** @var int|false $appointmentCount */
        $appointmentCount = $request->fetch(\PDO::FETCH_COLUMN);

        return $appointmentCount ?: 0;
    }
}
