<?php

namespace App\Models\Bois;

use App\Models\Model;
use App\Core\Exceptions\Client\ClientException;

class RdvModel extends Model
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
     * Récupère tous les RDV bois.
     * 
     * @param array $filtre Filtre qui contient...
     */
    public function readAll(array $query): array
    {
        // Filtre
        $startDate = isset($query['date_debut']) ? ($query['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
        $endDate = isset($query['date_fin']) ? ($query['date_fin'] ?: "9999-12-31") : "9999-12-31";
        $supplierFilter = trim($query['fournisseur'] ?? "", ",");
        $clientFilter = trim($query['client'] ?? "", ",");
        $loadingFilter = trim($query['chargement'] ?? "", ",");
        $deliveryFilter = trim($query['livraison'] ?? "", ",");
        $transportFilter = trim($query['transporteur'] ?? "", ",");
        $chartererFilter = trim($query['affreteur'] ?? "", ",");

        $sqlSupplierFilter = $supplierFilter === "" ? "" : " AND fournisseur IN ($supplierFilter)";
        $sqlClientFilter = $clientFilter === "" ? "" : " AND client IN ($clientFilter)";
        $sqlLoadingFilter = $loadingFilter === "" ? "" : " AND chargement IN ($loadingFilter)";
        $sqlDeliveryFilter = $deliveryFilter === "" ? "" : " AND livraison IN ($deliveryFilter)";
        $sqlTransportFilter = $transportFilter === "" ? "" : " AND transporteur IN ($transportFilter)";
        $sqlChartererFilter = $chartererFilter === "" ? "" : " AND affreteur IN ($chartererFilter)";

        $sqlFilter =
            $sqlSupplierFilter
            . $sqlClientFilter
            . $sqlLoadingFilter
            . $sqlDeliveryFilter
            . $sqlTransportFilter
            . $sqlChartererFilter;

        $statement =
            "SELECT
                id,
                attente,
                date_rdv,
                heure_arrivee,
                heure_depart,
                confirmation_affretement,
                commande_prete,
                numero_bl,
                commentaire_public,
                commentaire_cache,
                client,
                chargement,
                livraison,
                affreteur,
                fournisseur,
                transporteur
            FROM bois_planning
            WHERE 
            (
                (date_rdv BETWEEN :date_debut AND :date_fin)
                OR date_rdv IS NULL
                OR attente = 1
            )
            $sqlFilter
            ORDER BY date_rdv";

        $request = $this->mysql->prepare($statement);

        $request->execute([
            "date_debut" => $startDate,
            "date_fin" => $endDate
        ]);

        $appointments = $request->fetchAll();

        // Rétablissement des types bool
        array_walk_recursive($appointments, function (&$value, $key) {
            $value = match ($key) {
                "attente",
                "confirmation_affretement",
                "commande_prete",
                "lie_agence" => (bool) $value,
                default => $value,
            };
        });

        return $appointments;
    }

    /**
     * Récupère un RDV bois.
     * 
     * @param int $id ID du RDV à récupérer
     * 
     * @return array Rendez-vous récupéré
     */
    public function read($id): ?array
    {
        $statement =
            "SELECT
                id,
                attente,
                date_rdv,
                heure_arrivee,
                heure_depart,
                confirmation_affretement,
                commande_prete,
                numero_bl,
                commentaire_public,
                commentaire_cache,
                client,
                chargement,
                livraison,
                affreteur,
                fournisseur,
                transporteur
            FROM bois_planning
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(["id" => $id]);
        $appointment = $request->fetch();

        if (!$appointment) return null;

        // Rétablissement des types bool
        array_walk_recursive($appointment, function (&$value, $key) {
            $value = match ($key) {
                "attente",
                "confirmation_affretement",
                "commande_prete",
                "lie_agence" => (bool) $value,
                default => $value,
            };
        });

        return $appointment;
    }

    /**
     * Crée un RDV bois.
     * 
     * @param array $input Eléments du RDV à créer
     * 
     * @return array Rendez-vous créé
     */
    public function create(array $input): array
    {
        $statement =
            "INSERT INTO bois_planning
            VALUES(
                NULL,
                :attente,
                :date_rdv,
                :heure_arrivee,
                :heure_depart,
                :chargement,
                :client,
                :livraison,
                :transporteur,
                :affreteur,
                :fournisseur,
                :commande_prete,
                :confirmation_affretement,
                :numero_bl,
                :commentaire_public,
                :commentaire_cache
            )";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'attente' => (int) $input["attente"],
            'date_rdv' => $input["date_rdv"] ?: NULL,
            'heure_arrivee' => $input["heure_arrivee"] ?: NULL,
            'heure_depart' => $input["heure_depart"] ?: NULL,
            'chargement' => $input["chargement"],
            'client' => $input["client"],
            'livraison' => $input["livraison"] ?: NULL,
            'transporteur' => $input["transporteur"] ?: NULL,
            'affreteur' => $input["affreteur"] ?: NULL,
            'fournisseur' => $input["fournisseur"],
            'commande_prete' => (int) $input["commande_prete"],
            'confirmation_affretement' => (int) $input["confirmation_affretement"],
            'numero_bl' => $input["numero_bl"],
            'commentaire_public' => $input["commentaire_public"],
            'commentaire_cache' => $input["commentaire_cache"],
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->read($lastInsertId);
    }

    /**
     * Met à jour un RDV bois.
     * 
     * @param int   $id ID du RDV à modifier
     * @param array $input  Eléments du RDV à modifier
     * 
     * @return array RDV modifié
     */
    public function update(int $id, array $input): array
    {
        $statement =
            "UPDATE bois_planning
            SET
                attente = :attente,
                date_rdv = :date_rdv,
                heure_arrivee = :heure_arrivee,
                heure_depart = :heure_depart,
                chargement = :chargement,
                client = :client,
                livraison = :livraison,
                transporteur = :transporteur,
                affreteur = :affreteur,
                fournisseur = :fournisseur,
                commande_prete = :commande_prete,
                confirmation_affretement = :confirmation_affretement,
                numero_bl = :numero_bl,
                commentaire_public = :commentaire_public,
                commentaire_cache = :commentaire_cache
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'attente' => (int) $input["attente"],
            'date_rdv' => $input["date_rdv"] ?: NULL,
            'heure_arrivee' => $input["heure_arrivee"] ?: NULL,
            'heure_depart' => $input["heure_depart"] ?: NULL,
            'client' => $input["client"],
            'chargement' => $input["chargement"],
            'livraison' => $input["livraison"] ?: NULL,
            'transporteur' => $input["transporteur"] ?: NULL,
            'affreteur' => $input["affreteur"] ?: NULL,
            'fournisseur' => $input["fournisseur"],
            'commande_prete' => (int) $input["commande_prete"],
            'confirmation_affretement' => (int) $input["confirmation_affretement"],
            'numero_bl' => $input["numero_bl"],
            'commentaire_public' => $input["commentaire_public"],
            'commentaire_cache' => $input["commentaire_cache"],
            'id' => $id,
        ]);

        return $this->read($id);
    }

    /**
     * Met à jour certaines proriétés d'un RDV bois.
     * 
     * @param int   $id    id du RDV à modifier
     * @param array $input Données à modifier
     * 
     * @return array RDV modifié
     */
    public function patch(int $id, array $input): array
    {
        /**
         * Confirmation affrètement
         */
        if (isset($input["confirmation_affretement"])) {
            $this
                ->mysql
                ->prepare(
                    "UPDATE bois_planning
                    SET confirmation_affretement = :charterConfirmation
                    WHERE id = :id"
                )
                ->execute([
                    'charterConfirmation' => (int) $input["confirmation_affretement"],
                    'id' => $id,
                ]);
        }

        /**
         * Heure d'arrivée (+ numéro BL auto le cas échéant)
         */
        if (isset($input["heure_arrivee"])) {

            // Heure
            $time = date('H:i:s');
            $this
                ->mysql
                ->prepare("UPDATE bois_planning SET heure_arrivee = :arrivalTime WHERE id = :id")
                ->execute([
                    'arrivalTime' => $time,
                    'id' => $id
                ]);


            // Numéro BL automatique (Stora Enso)      
            $appointment = $this->read($id);

            if (
                $appointment["fournisseur"] === 292 /* Stora Enso */
                && $appointment["chargement"] === 1 /* AMSB */
            ) {
                // Récupération du numéro de BL du RDV à modifier (si déjà renseigné)
                $currentBlNumberRequest = $this->mysql->prepare(
                    "SELECT numero_bl
                    FROM bois_planning
                    WHERE id = :id"
                );
                $currentBlNumberRequest->execute(["id" => $id]);
                $currentBlNumberRequest = $currentBlNumberRequest->fetch();
                $currentBlNumber = $currentBlNumberRequest["numero_bl"];

                // Dernier numéro de BL de Stora Enso :
                // - enregistrement des 10 derniers numéros dans un tableau
                // - tri du tableau
                // - récupération du numéro le plus élevé
                // Ceci permet de prendre en compte les cas où le dernier numéro
                // renseigné n'est pas le plus haut numériquement
                // Permet aussi de prendre en compte les éventuels bons sans numéro "numérique"
                $precedingBlNumbersRequest = $this->mysql->query(
                    "SELECT numero_bl
                    FROM bois_planning
                    WHERE fournisseur = {$appointment["fournisseur"]}
                    AND numero_bl != ''
                    ORDER BY
                        date_rdv DESC,
                        heure_arrivee DESC,
                        numero_bl DESC
                    LIMIT 10"
                )->fetchAll();

                $precedingBlNumbers = [];

                foreach ($precedingBlNumbersRequest as $blNumber) {
                    // Si le dernier numéro de BL est composé (ex: "200101 + 200102")
                    // alors séparation/tri de la chaîne de caractères puis récupération du numéro le plus élevé
                    $matches = NULL; // Tableau pour récupérer les numéros de BL
                    preg_match_all("/\d{6}/", $blNumber["numero_bl"], $matches); // Filtre sur les numéros valides (6 chiffres)
                    $matches = $matches[0]; // Extraction des résultats
                    sort($matches); // Tri des numéros
                    $precedingBlNumbers[] = array_pop($matches); // Récupération du numéro le plus élevé
                }

                // Tri des 10 derniers numéros de BL puis récupération du plus élevé
                sort($precedingBlNumbers);
                $precedingBlNumber = array_pop($precedingBlNumbers);

                // Calcul du nouveau numéro de BL (si possible)
                // Insertion du nouveau numéro de BL si numéro non déjà renseigné
                $newBlNumber = is_numeric($precedingBlNumber) ? $precedingBlNumber + 1 : '';
                if ($currentBlNumber === '' && $newBlNumber) {
                    $updateBlNumberRequest = $this->mysql->prepare(
                        "UPDATE bois_planning
                        SET numero_bl = :newBlNumber
                        WHERE id = :id"
                    );

                    $updateBlNumberRequest->execute([
                        'newBlNumber' => $newBlNumber,
                        'id' => $id
                    ]);
                }
            }
        }

        /**
         * Heure de départ
         */
        if (isset($input["heure_depart"])) {
            $time = date('H:i:s');
            $this->mysql
                ->prepare("UPDATE bois_planning SET heure_depart = :heure WHERE id = :id")
                ->execute([
                    'heure' => $time,
                    'id' => $id
                ]);
        }

        /**
         * Numéro de BL
         */
        if (isset($input["numero_bl"])) {
            $blNumber = $input['numero_bl'];
            $dryRun = $input["dry_run"] ?? FALSE;

            $blNumberExists = FALSE;

            // Fournisseurs dont le numéro de BL doit être unique
            $suppliersWithUniqueBlNumbers = [
                292 // Stora Enso
            ];

            $appointment = $this->mysql
                ->query(
                    "SELECT p.fournisseur, f.nom_court AS fournisseur_nom
                    FROM bois_planning p
                    JOIN tiers f ON f.id = p.fournisseur
                    WHERE p.id = {$id}"
                )->fetch();

            // Vérification si le numéro de BL existe déjà (pour Enso)
            if (
                in_array($appointment["fournisseur"], $suppliersWithUniqueBlNumbers)
                && $blNumber !== ""
                && $blNumber !== "-"
            ) {
                $updateBlNumberRequest = $this->mysql->prepare(
                    "SELECT COUNT(*) AS countOfSameBlNumber, id AS idOfSameBlNumber
                    FROM bois_planning
                    WHERE numero_bl LIKE CONCAT('%', :blNumber, '%')
                    AND fournisseur = :supplierId
                    AND NOT id = :id"
                );
                $updateBlNumberRequest->execute([
                    "blNumber" => $blNumber,
                    "supplierId" => $appointment["fournisseur"],
                    "id" => $id
                ]);

                [$countOfSameBlNumber, $idOfSameBlNumber] = $updateBlNumberRequest->fetch(\PDO::FETCH_NUM);

                $blNumberExists = $countOfSameBlNumber > 0;
            }

            if (!$blNumberExists && !$dryRun) {
                $this
                    ->mysql
                    ->prepare(
                        "UPDATE bois_planning
                        SET numero_bl = :newBlNumber
                        WHERE id = :id"
                    )
                    ->execute([
                        'newBlNumber' => $blNumber,
                        'id' => $id
                    ]);
            }

            // Si le numéro de BL existe déjà (pour Enso), message d'erreur
            if ($blNumberExists && $id != $idOfSameBlNumber) {
                throw new ClientException("Le numéro de BL {$blNumber} existe déjà pour {$appointment["fournisseur_nom"]}.");
            }
        }

        /**
         * Commande prête
         */
        if (isset($input["commande_prete"])) {
            $this->mysql
                ->prepare(
                    "UPDATE bois_planning
                    SET commande_prete = :commande_prete
                    WHERE id = :id"
                )
                ->execute([
                    'commande_prete' => (int) $input["commande_prete"],
                    'id' => $id,
                ]);
        }

        return $this->read($id);
    }

    /**
     * Supprime un RDV bois.
     * 
     * @param int $id ID du RDV à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function delete(int $id): bool
    {
        $request = $this->mysql->prepare("DELETE FROM bois_planning WHERE id = :id");
        $isDeleted = $request->execute(["id" => $id]);

        return $isDeleted;
    }
}
