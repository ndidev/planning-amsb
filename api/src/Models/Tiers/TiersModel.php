<?php

namespace App\Models\Tiers;

use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
use App\Models\Model;
use App\Core\Logger\ErrorLogger;

class TiersModel extends Model
{
    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function exists(int $id)
    {
        return $this->mysql->exists("tiers", $id);
    }

    /**
     * Récupère tous les tiers.
     * 
     * @param array $options Options de récupération
     * 
     * @return array Liste des tiers
     */
    public function readAll(): array
    {
        $statement = "SELECT * FROM tiers ORDER BY nom_court, ville";

        $thirdParties = $this->mysql->query($statement)->fetchAll();

        foreach ($thirdParties as &$thirdParty) {
            // Changement TINYINT en booléen
            $thirdParty["id"] = (int) $thirdParty["id"];
            $thirdParty["bois_fournisseur"] = (bool) $thirdParty["bois_fournisseur"];
            $thirdParty["bois_client"] = (bool) $thirdParty["bois_client"];
            $thirdParty["bois_transporteur"] = (bool) $thirdParty["bois_transporteur"];
            $thirdParty["bois_affreteur"] = (bool) $thirdParty["bois_affreteur"];
            $thirdParty["vrac_fournisseur"] = (bool) $thirdParty["vrac_fournisseur"];
            $thirdParty["vrac_client"] = (bool) $thirdParty["vrac_client"];
            $thirdParty["vrac_transporteur"] = (bool) $thirdParty["vrac_transporteur"];
            $thirdParty["maritime_affreteur"] = (bool) $thirdParty["maritime_affreteur"];
            $thirdParty["maritime_armateur"] = (bool) $thirdParty["maritime_armateur"];
            $thirdParty["maritime_courtier"] = (bool) $thirdParty["maritime_courtier"];
            $thirdParty["non_modifiable"] = (bool) $thirdParty["non_modifiable"];
            $thirdParty["lie_agence"] = (bool) $thirdParty["lie_agence"];
            $thirdParty["actif"] = (bool) $thirdParty["actif"];

            // Modififaction de l'adresse du logo
            if ($thirdParty["logo"]) {
                $thirdParty["logo"] = $_ENV["LOGOS_URL"] . "/" . $thirdParty["logo"];
            }
        }

        return $thirdParties;
    }

    /**
     * Récupère un tiers.
     * 
     * @param int   $id      ID du tiers à récupérer
     * @param array $options Options de récupération
     * 
     * @return array Tiers récupéré
     */
    public function read($id): ?array
    {
        $statement = "SELECT * FROM tiers WHERE id = :id";

        $thirdPartyRequest = $this->mysql->prepare($statement);
        $thirdPartyRequest->execute(["id" => $id]);
        $thirdParty = $thirdPartyRequest->fetch();

        if (!$thirdParty) return null;

        $thirdParty["id"] = (int) $thirdParty["id"];
        $thirdParty["bois_fournisseur"] = (bool) $thirdParty["bois_fournisseur"];
        $thirdParty["bois_client"] = (bool) $thirdParty["bois_client"];
        $thirdParty["bois_transporteur"] = (bool) $thirdParty["bois_transporteur"];
        $thirdParty["vrac_fournisseur"] = (bool) $thirdParty["vrac_fournisseur"];
        $thirdParty["bois_affreteur"] = (bool) $thirdParty["bois_affreteur"];
        $thirdParty["vrac_client"] = (bool) $thirdParty["vrac_client"];
        $thirdParty["vrac_transporteur"] = (bool) $thirdParty["vrac_transporteur"];
        $thirdParty["maritime_affreteur"] = (bool) $thirdParty["maritime_affreteur"];
        $thirdParty["maritime_armateur"] = (bool) $thirdParty["maritime_armateur"];
        $thirdParty["maritime_courtier"] = (bool) $thirdParty["maritime_courtier"];
        $thirdParty["non_modifiable"] = (bool) $thirdParty["non_modifiable"];
        $thirdParty["lie_agence"] = (bool) $thirdParty["lie_agence"];
        $thirdParty["actif"] = (bool) $thirdParty["actif"];

        // Modififaction de l'adresse du logo
        if ($thirdParty["logo"]) {
            $thirdParty["logo"] = $_ENV["LOGOS_URL"] . "/" . $thirdParty["logo"];
        }

        return $thirdParty;
    }

    /**
     * Crée un tiers.
     * 
     * @param array $input Eléments du tiers à créer
     * 
     * @return array Tiers créé
     */
    public function create(array $input): array
    {
        // Enregistrement du logo dans le dossier images
        $logo = $this->saveLogo($input["logo"] ?? NULL) ?: NULL;

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
            'bois_fournisseur' => (int) $input["bois_fournisseur"],
            'bois_client' => (int) $input["bois_client"],
            'bois_transporteur' => (int) $input["bois_transporteur"],
            'bois_affreteur' => (int) $input["bois_affreteur"],
            'vrac_fournisseur' => (int) $input["vrac_fournisseur"],
            'vrac_client' => (int) $input["vrac_client"],
            'vrac_transporteur' => (int) $input["vrac_transporteur"],
            'maritime_armateur' => (int) $input["maritime_armateur"],
            'maritime_affreteur' => (int) $input["maritime_affreteur"],
            'maritime_courtier' => (int) $input["maritime_courtier"],
            'non_modifiable' => (int) $input["non_modifiable"],
            'lie_agence' => 0,
            'logo' => $logo,
            'actif' => 1,
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->read($lastInsertId);
    }

    /**
     * Met à jour un tiers.
     * 
     * @param int   $id ID du tiers à modifier
     * @param array $input  Eléments du tiers à modifier
     * 
     * @return array tiers modifié
     */
    public function update($id, array $input): array
    {
        // Enregistrement du logo dans le dossier images
        $logo = $this->saveLogo($input["logo"]);

        // Si un logo a été ajouté, l'utiliser, sinon, ne pas changer
        $logoStatement = $logo !== false ? ":logo" : "logo";

        $statement =
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

        $request = $this->mysql->prepare($statement);

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
            'bois_fournisseur' => (int) $input["bois_fournisseur"],
            'bois_client' => (int) $input["bois_client"],
            'bois_transporteur' => (int) $input["bois_transporteur"],
            'bois_affreteur' => (int) $input["bois_affreteur"],
            'vrac_fournisseur' => (int) $input["vrac_fournisseur"],
            'vrac_client' => (int) $input["vrac_client"],
            'vrac_transporteur' => (int) $input["vrac_transporteur"],
            'maritime_armateur' => (int) $input["maritime_armateur"],
            'maritime_affreteur' => (int) $input["maritime_affreteur"],
            'maritime_courtier' => (int) $input["maritime_courtier"],
            'actif' => (int) $input["actif"],
            'id' => $id,
        ];

        if ($logo !== false) {
            $fields["logo"] = $logo;
        }

        $request->execute($fields);

        return $this->read($id);
    }

    /**
     * Supprime un tiers.
     * 
     * @param int $id ID du tiers à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function delete(int $id): bool
    {
        $numberOfAppointments = (new NombreRdvModel())->read($id)["nombre_rdv"];
        if ($numberOfAppointments > 0) {
            throw new ClientException("Le tiers est concerné par $numberOfAppointments rdv. Impossible de le supprimer.");
        }

        $deleteRequest = $this->mysql->prepare("DELETE FROM tiers WHERE id = :id");
        $isDeleted = $deleteRequest->execute(["id" => $id]);

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
}
