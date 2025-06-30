<?php

// Path: api/src/Repository/ThirdPartyRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\ThirdParty\ThirdParty;
use App\Entity\ThirdParty\ThirdPartyContact;
use App\Service\ThirdPartyService;

/**
 * @phpstan-import-type ThirdPartyArray from \App\Entity\ThirdParty\ThirdParty
 * @phpstan-import-type ThirdPartyContactArray from \App\Entity\ThirdParty\ThirdPartyContact
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
        $thirdPartiesStatement = "SELECT * FROM tiers ORDER BY nom_court, ville";

        try {
            /** @var ThirdPartyArray[] */
            $thirdPartiesRaw = $this->mysql->prepareAndExecute($thirdPartiesStatement)->fetchAll();
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la récupération des tiers.", previous: $e);
        }

        $thirdParties = \array_map(
            fn($thirdPartyRaw) => $this->thirdPartyService->makeThirdPartyFromDatabase($thirdPartyRaw),
            $thirdPartiesRaw
        );

        $idsOfThirdPartiesWithContacts = $this->mysql
            ->prepareAndExecute("SELECT DISTINCT tiers FROM tiers_contacts")
            ->fetchAll(\PDO::FETCH_COLUMN, 0);

        foreach ($thirdParties as $thirdParty) {
            /** @var int */
            $id = $thirdParty->id;

            if (\in_array($id, $idsOfThirdPartiesWithContacts, true)) {
                $thirdParty->contacts = $this->fetchContactsForThirdParty($id);
            } else {
                $thirdParty->contacts = [];
            }
        }

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
                    $thirdPartyStatement = "SELECT * FROM tiers WHERE id = :id";

                    /** @var ThirdPartyArray */
                    $thirdPartyRaw = $this->mysql
                        ->prepareAndExecute($thirdPartyStatement, ["id" => $id])
                        ->fetch();

                    $thirdParty = $this->thirdPartyService->makeThirdPartyFromDatabase($thirdPartyRaw);

                    $thirdParty->contacts = $this->fetchContactsForThirdParty($id);

                    return $thirdParty;
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
     * Récupère les contacts d'un tiers.
     * 
     * @param int $id ID du tiers dont on veut les contacts.
     * 
     * @return ThirdPartyContact[] Liste des contacts du tiers
     */
    public function fetchContactsForThirdParty(int $id): array
    {
        $statement = "SELECT * FROM tiers_contacts WHERE tiers = :id ORDER BY nom";

        try {
            /** @var ThirdPartyContactArray[] */
            $contactsRaw = $this->mysql
                ->prepareAndExecute($statement, ["id" => $id])
                ->fetchAll();
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la récupération des contacts du tiers.", previous: $e);
        }

        $contacts = \array_map(
            fn($contactRaw) => $this->thirdPartyService->makeThirdPartyContactFromDatabase($contactRaw),
            $contactsRaw
        );

        return $contacts;
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
        $thirdPartyStatement =
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
                roles = :roles,
                logo = :logo,
                actif = :active";

        $contactsStatement = "INSERT INTO tiers_contacts
            SET
                tiers = :thirdPartyId,
                nom = :name,
                email = :email,
                telephone = :phone,
                fonction = :position,
                commentaire = :comments";

        try {
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute($thirdPartyStatement, [
                'shortName' => $thirdParty->shortName ?: $thirdParty->fullName,
                'fullName' => $thirdParty->fullName,
                'addressLine1' => $thirdParty->addressLine1,
                'addressLine2' => $thirdParty->addressLine2,
                'postCode' => $thirdParty->postCode,
                'city' => $thirdParty->city,
                'country' => $thirdParty->country?->iso,
                'phone' => $thirdParty->phone,
                'comments' => $thirdParty->comments,
                'roles' => \json_encode($thirdParty->roles),
                'logo' => $thirdParty->logoFilename,
                'active' => (int) $thirdParty->isActive,
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            $this->mysql->prepareAndExecute($contactsStatement, \array_map(
                fn($contact) => [
                    'thirdPartyId' => $lastInsertId,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'position' => $contact->position,
                    'comments' => $contact->comments,
                ],
                $thirdParty->contacts
            ));

            $this->mysql->commit();
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            throw new DBException("Erreur lors de la création du tiers.", previous: $e);
        }

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
        $logoStatement = $thirdParty->logoFilename !== false ? ":logo" : "logo";

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

        $thirdPartyFields = [
            'shortName' => $thirdParty->shortName ?: $thirdParty->fullName,
            'fullName' => $thirdParty->fullName,
            'addressLine1' => $thirdParty->addressLine1,
            'addressLine2' => $thirdParty->addressLine2,
            'postCode' => $thirdParty->postCode,
            'city' => $thirdParty->city,
            'country' => $thirdParty->country?->iso,
            'phone' => $thirdParty->phone,
            'comments' => $thirdParty->comments,
            'roles' => \json_encode($thirdParty->roles),
            'active' => (int) $thirdParty->isActive,
            'id' => $thirdParty->id,
        ];

        if ($thirdParty->logoFilename !== false) {
            $thirdPartyFields["logo"] = $thirdParty->logoFilename;
        }

        $contactStatement = "INSERT INTO tiers_contacts
            SET
                id = :id,
                tiers = :thirdPartyId,
                nom = :name,
                email = :email,
                telephone = :phone,
                fonction = :position,
                commentaire = :comments
            ON DUPLICATE KEY UPDATE
                nom = :name,
                email = :email,
                telephone = :phone,
                fonction = :position,
                commentaire = :comments";

        try {
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute($thirdPartyStatement, $thirdPartyFields);

            // CONTACTS
            // Delete contacts that are not in the submitted list
            // !! DELETION TO BE PLACED *BEFORE* ADDING NEW CONTACTS TO AVOID IMMEDIATE DELETION AFTER INSERTION !!
            // Compare the array passed by POST with the existing list of contacts for the relevant third party.
            /** @var array<int> */
            $existingContactIds = $this->mysql
                ->prepareAndExecute(
                    "SELECT id FROM tiers_contacts WHERE tiers = :thirdPartyId",
                    ['thirdPartyId' => $thirdParty->id]
                )->fetchAll(\PDO::FETCH_COLUMN, 0);

            $submittedContactIds = \array_map(fn($contact) => $contact->id, $thirdParty->contacts);
            $contactIdsToBeDeleted = \array_diff($existingContactIds, $submittedContactIds);

            if (!empty($contactIdsToBeDeleted)) {
                $deleteContactsStatement = "DELETE FROM tiers_contacts WHERE id IN (" . \implode(",", $contactIdsToBeDeleted) . ")";
                $this->mysql->exec($deleteContactsStatement);
            }

            // Insert and update qualities
            $this->mysql->prepareAndExecute($contactStatement, \array_map(
                fn($contact) => [
                    'id' => $contact->id,
                    'thirdPartyId' => $thirdParty->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'position' => $contact->position,
                    'comments' => $contact->comments,
                ],
                $thirdParty->contacts
            ));

            $this->mysql->commit();
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            throw new DBException("Erreur lors de la mise à jour du tiers.", previous: $e);
        }

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
            $this->mysql->prepareAndExecute($deleteStatement, ["id" => $id]);
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
