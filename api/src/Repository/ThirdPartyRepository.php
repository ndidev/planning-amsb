<?php

// Path: api/src/Repository/ThirdPartyRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\ThirdParty;
use App\Service\ThirdPartyService;

/**
 * @phpstan-import-type ThirdPartyArray from \App\Entity\ThirdParty
 */
final class ThirdPartyRepository extends Repository
{
    /** @var \ReflectionClass<ThirdParty> */
    private \ReflectionClass $reflector;

    public function __construct(private ThirdPartyService $thirdPartyService)
    {
        $this->reflector = new \ReflectionClass(ThirdParty::class);
    }

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

        $thirdPartiesRequest = $this->mysql->query($statement);

        if (!$thirdPartiesRequest) {
            throw new DBException("Impossible de récupérer les tiers.");
        }

        /** @var ThirdPartyArray[] */
        $thirdPartiesRaw = $thirdPartiesRequest->fetchAll();

        $thirdParties = \array_map(
            fn($thirdPartyRaw) => $this->thirdPartyService->makeThirdPartyFromDatabase($thirdPartyRaw),
            $thirdPartiesRaw
        );

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
        /** @var array<int, ThirdParty> */
        static $cache = [];

        if (isset($cache[$id])) {
            return $cache[$id];
        }

        if (!$this->thirdPartyExists($id)) {
            return null;
        }

        /** @var ThirdParty */
        $thirdParty = $this->reflector->newLazyProxy(
            function () use ($id) {
                try {
                    $statement = "SELECT * FROM tiers WHERE id = :id";

                    /** @var ThirdPartyArray */
                    $thirdPartyRaw = $this->mysql
                        ->prepareAndExecute($statement, ["id" => $id])
                        ->fetch();

                    return $this->thirdPartyService->makeThirdPartyFromDatabase($thirdPartyRaw);
                } catch (\PDOException $e) {
                    throw new DBException("Erreur lors de la récupération du tiers.", previous: $e);
                }
            }
        );

        $this->reflector->getProperty('id')->setRawValueWithoutLazyInitialization($thirdParty, $id);

        $cache[$id] = $thirdParty;

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
            'country' => $thirdParty->getCountry()?->getISO(),
            'phone' => $thirdParty->getPhone(),
            'comments' => $thirdParty->getComments(),
            'roles' => \json_encode($thirdParty->getRoles()),
            'logo' => $thirdParty->getLogoFilename(),
            'active' => (int) $thirdParty->isActive(),
        ]);

        $lastInsertId = (int) $this->mysql->lastInsertId();
        $this->mysql->commit();

        /** @var ThirdParty */
        $newThirdParty = $this->fetchThirdParty($lastInsertId);

        return $newThirdParty;
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
        $id = $thirdParty->id;

        if (!$id) {
            throw new ClientException("Impossible de mettre à jour un tiers sans ID.");
        }

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
            'country' => $thirdParty->getCountry()?->getISO(),
            'phone' => $thirdParty->getPhone(),
            'comments' => $thirdParty->getComments(),
            'roles' => \json_encode($thirdParty->getRoles()),
            'active' => (int) $thirdParty->isActive(),
            'id' => $thirdParty->id,
        ];

        if ($thirdParty->getLogoFilename() !== false) {
            $fields["logo"] = $thirdParty->getLogoFilename();
        }

        $request->execute($fields);

        /** @var ThirdParty */
        $updatedThirdParty = $this->fetchThirdParty($id);

        return $updatedThirdParty;
    }

    /**
     * Supprime un tiers.
     * 
     * @param int $id ID du tiers à supprimer
     */
    public function deleteThirdParty(int $id): void
    {
        $appointmentCount = $this->fetchAppointmentCountForId($id);
        if ($appointmentCount > 0) {
            throw new ClientException("Le tiers est concerné par {$appointmentCount} rdv. Impossible de le supprimer.");
        }

        try {
            $deleteStatement = "DELETE FROM tiers WHERE id = :id";
            $deleteRequest = $this->mysql->prepare($deleteStatement);
            $deleteRequest->execute(["id" => $id]);
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la suppression.", previous: $e);
        }
    }

    /**
     * Récupère le nombre de RDV pour un tiers.
     * 
     * @param int $id Optionnel. ID du tiers à récupérer.
     * 
     * @return int
     */
    public function fetchAppointmentCountForId(int $id): int
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
