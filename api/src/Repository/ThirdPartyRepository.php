<?php

namespace App\Repository;

use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\Logger\ErrorLogger;
use App\Entity\ThirdParty;

class ThirdPartyRepository extends Repository
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
    public function thirdPartyExists(int $id)
    {
        return $this->mysql->exists("tiers", $id);
    }

    /**
     * Récupère tous les tiers.
     * 
     * @return ThirdParty[] Liste des tiers
     */
    public function getThirdParties(): array
    {
        $statement = "SELECT * FROM tiers ORDER BY nom_court, ville";

        $thirdPartiesRaw = $this->mysql->query($statement)->fetchAll();

        $thirdParties = array_map(fn(array $thirdPartyRaw) => new ThirdParty($thirdPartyRaw), $thirdPartiesRaw);

        static::$cache = $thirdParties;

        return $thirdParties;
    }

    /**
     * Récupère un tiers.
     * 
     * @param int   $id      ID du tiers à récupérer
     * @param array $options Options de récupération
     * 
     * @return ?ThirdParty Tiers récupéré
     */
    public function getThirdParty(int $id): ?ThirdParty
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

        $thirdParty = new ThirdParty($thirdPartyRaw);

        array_push(static::$cache, $thirdParty);

        return $thirdParty;
    }

    /**
     * Crée un tiers.
     * 
     * @param array $input Eléments du tiers à créer
     * 
     * @return ThirdParty Tiers créé
     */
    public function createThirdParty(array $input): ThirdParty
    {
        // Enregistrement du logo dans le dossier images
        $logoFilename = $this->saveLogo($input["logo"] ?? NULL) ?: NULL;

        $statement =
            "INSERT INTO tiers
            VALUES(
            NULL,
                :nom_court,
                :nom_complet,
                :adresse_ligne_1,
                :adresse_ligne_2,
                :cp,
                :ville,
                :pays,
                :telephone,
                :commentaire,
                :bois_fournisseur,
                :bois_client,
                :bois_transporteur,
                :bois_affreteur,
                :vrac_fournisseur,
                :vrac_client,
                :vrac_transporteur,
                :maritime_armateur,
                :maritime_affreteur,
                :maritime_courtier,
                :non_modifiable,
                :lie_agence,
                :logo,
                :actif
            )";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'nom_court' => $input["nom_court"] ?: $input["nom_complet"],
            'nom_complet' => $input["nom_complet"],
            'adresse_ligne_1' => $input["adresse_ligne_1"],
            'adresse_ligne_2' => $input["adresse_ligne_2"],
            'cp' => $input["cp"],
            'ville' => $input["ville"],
            'pays' => $input["pays"],
            'telephone' => $input["telephone"],
            'commentaire' => $input["commentaire"],
            'bois_fournisseur' => (int) $input["roles"]["bois_fournisseur"],
            'bois_client' => (int) $input["roles"]["bois_client"],
            'bois_transporteur' => (int) $input["roles"]["bois_transporteur"],
            'bois_affreteur' => (int) $input["roles"]["bois_affreteur"],
            'vrac_fournisseur' => (int) $input["roles"]["vrac_fournisseur"],
            'vrac_client' => (int) $input["roles"]["vrac_client"],
            'vrac_transporteur' => (int) $input["roles"]["vrac_transporteur"],
            'maritime_armateur' => (int) $input["roles"]["maritime_armateur"],
            'maritime_affreteur' => (int) $input["roles"]["maritime_affreteur"],
            'maritime_courtier' => (int) $input["roles"]["maritime_courtier"],
            'non_modifiable' => (int) $input["non_modifiable"],
            'lie_agence' => 0,
            'logo' => $logoFilename,
            'actif' => 1,
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->getThirdParty($lastInsertId);
    }

    /**
     * Met à jour un tiers.
     * 
     * @param int   $id ID du tiers à modifier
     * @param array $input  Eléments du tiers à modifier
     * 
     * @return ThirdParty tiers modifié
     */
    public function updateThirdParty($id, array $input): ThirdParty
    {
        // Enregistrement du logo dans le dossier images
        $logoFilename = $this->saveLogo($input["logo"]);

        // Si un logo a été ajouté, l'utiliser, sinon, ne pas changer
        $logoStatement = $logoFilename !== false ? ":logo" : "logo";

        $thirdPartyStatement =
            "UPDATE tiers
            SET
                nom_court = :nom_court,
                nom_complet = :nom_complet,
                adresse_ligne_1 = :adresse_ligne_1,
                adresse_ligne_2 = :adresse_ligne_2,
                cp = :cp,
                ville = :ville,
                pays = :pays,
                telephone = :telephone,
                commentaire = :commentaire,
                bois_fournisseur = :bois_fournisseur,
                bois_client = :bois_client,
                bois_transporteur = :bois_transporteur,
                bois_affreteur = :bois_affreteur,
                vrac_fournisseur = :vrac_fournisseur,
                vrac_client = :vrac_client,
                vrac_transporteur = :vrac_transporteur,
                maritime_armateur = :maritime_armateur,
                maritime_affreteur = :maritime_affreteur,
                maritime_courtier = :maritime_courtier,
                logo = $logoStatement,
                actif = :actif
            WHERE id = :id";

        $request = $this->mysql->prepare($thirdPartyStatement);

        $fields = [
            'nom_court' => $input["nom_court"] ?: $input["nom_complet"],
            'nom_complet' => $input["nom_complet"],
            'adresse_ligne_1' => $input["adresse_ligne_1"],
            'adresse_ligne_2' => $input["adresse_ligne_2"],
            'cp' => $input["cp"],
            'ville' => $input["ville"],
            'pays' => $input["pays"],
            'telephone' => $input["telephone"],
            'commentaire' => $input["commentaire"],
            'bois_fournisseur' => (int) $input["roles"]["bois_fournisseur"],
            'bois_client' => (int) $input["roles"]["bois_client"],
            'bois_transporteur' => (int) $input["roles"]["bois_transporteur"],
            'bois_affreteur' => (int) $input["roles"]["bois_affreteur"],
            'vrac_fournisseur' => (int) $input["roles"]["vrac_fournisseur"],
            'vrac_client' => (int) $input["roles"]["vrac_client"],
            'vrac_transporteur' => (int) $input["roles"]["vrac_transporteur"],
            'maritime_armateur' => (int) $input["roles"]["maritime_armateur"],
            'maritime_affreteur' => (int) $input["roles"]["maritime_affreteur"],
            'maritime_courtier' => (int) $input["roles"]["maritime_courtier"],
            'actif' => (int) $input["actif"],
            'id' => $id,
        ];

        if ($logoFilename !== false) {
            $fields["logo"] = $logoFilename;
        }

        $request->execute($fields);

        return $this->getThirdParty($id);
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
        $appointmentCount = $this->getAppointmentCount($id);
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
     * Enregistrer un logo dans le dossier images
     * et retourne le hash du fichier.
     * 
     * @param array|string|null $file Données du fichier (null pour effacement du logo existant).
     * 
     * @return string|null|false Nom de fichier du logo si l'enregistrement a réussi, `false` sinon.
     */
    private function saveLogo(array|string|null $file): string|null|false
    {
        try {
            // Conservation du fichier existant
            if (gettype($file) === "string") {
                return false;
            }

            // Suppression du fichier existant
            if ($file === null) {
                return null;
            }

            // Récupérer les données de l'image
            // $fichier["data"] = "data:{type mime};base64,{données}"
            $data = explode(",", $file["data"])[1];

            // Création de l'image depuis les données
            $imageString = base64_decode($data);
            $image = imagecreatefromstring(base64_decode($data));

            if (!$image) {
                throw new ServerException("Logo : Erreur dans la création de l'image (imagecreatefromstring)");
            }


            // Redimensionnement
            define("MAX_HEIGHT", 500); // Hauteur maximale de l'image à enregistrer.

            [$width, $height] = getimagesizefromstring($imageString);
            $percent = min(MAX_HEIGHT / $height, 1);
            $newWidth = (int) ($width * $percent);
            $imageResized = imagescale($image, $newWidth);


            // Enregistrement
            $hash = hash("md5", $data);
            $filename = $hash . ".webp";
            $filepath = LOGOS . "/$filename";
            if (imagewebp($imageResized, $filepath, 100) === false) {
                throw new ServerException("Erreur dans l'enregistrement du logo (imagewebp)");
            }

            return $filename;
        } catch (\Throwable $e) {
            ErrorLogger::log($e);
            return false;
        }
    }

    /**
     * Récupère le nombre de RDV pour un tiers ou tous les tiers.
     * 
     * @param int $id Optionnel. ID du tiers à récupérer.
     * 
     * @return array Nombre de RDV pour le(s) tiers.
     */
    public function getAppointmentCount(?int $id = null): array
    {
        $statementWithoutId =
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

        $statementWithId =
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
            WHERE t.id = :id
            ";

        if (is_null($id)) {
            $request = $this->mysql->query($statementWithoutId);
            $appointmentCountWithoutKeys = $request->fetchAll();

            $appointmentCountWithKeys = [];
            foreach ($appointmentCountWithoutKeys as $appointmentCount) {
                if ($appointmentCount["nombre_rdv"] > 0) {
                    $appointmentCountWithKeys[$appointmentCount["id"]] = $appointmentCount["nombre_rdv"];
                }
            }
        } else {
            $request = $this->mysql->prepare($statementWithId);
            $request->execute(["id" => $id]);
            $appointmentCountWithKeys = $request->fetch();
        }

        return $appointmentCountWithKeys;
    }
}
