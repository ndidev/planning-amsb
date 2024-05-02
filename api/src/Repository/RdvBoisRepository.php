<?php

namespace App\Repository;

use App\Core\DateUtils;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\EntreeRegistreBoisDTO;
use App\Entity\Bois\RdvBois;
use App\Service\BoisService;

class RdvBoisRepository extends Repository
{
    /**
     * @var RdvBois[]
     */
    static private array $cache = [];

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function rdvExiste(int $id): bool
    {
        return $this->mysql->exists("bois_planning", $id);
    }

    /**
     * Récupère tous les RDV bois.
     * 
     * @param array $query Paramètres de recherche.
     * 
     * @return RdvBois[] Tous les RDV récupérés
     */
    public function getRdvs(array $query): array
    {
        // Filtre
        $date_debut = isset($query['date_debut']) ? ($query['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
        $date_fin = isset($query['date_fin']) ? ($query['date_fin'] ?: "9999-12-31") : "9999-12-31";
        $filtre_fournisseur = trim($query['fournisseur'] ?? "", ",");
        $filtre_client = trim($query['client'] ?? "", ",");
        $filtre_chargement = trim($query['chargement'] ?? "", ",");
        $filtre_livraison = trim($query['livraison'] ?? "", ",");
        $filtre_transporteur = trim($query['transporteur'] ?? "", ",");
        $filtre_affreteur = trim($query['affreteur'] ?? "", ",");

        $filtre_sql_fournisseur = $filtre_fournisseur === "" ? "" : " AND fournisseur IN ($filtre_fournisseur)";
        $filtre_sql_client = $filtre_client === "" ? "" : " AND client IN ($filtre_client)";
        $filtre_sql_chargement = $filtre_chargement === "" ? "" : " AND chargement IN ($filtre_chargement)";
        $filtre_sql_livraison = $filtre_livraison === "" ? "" : " AND livraison IN ($filtre_livraison)";
        $filtre_sql_transporteur = $filtre_transporteur === "" ? "" : " AND transporteur IN ($filtre_transporteur)";
        $filtre_sql_affreteur = $filtre_affreteur === "" ? "" : " AND affreteur IN ($filtre_affreteur)";

        $filtre_sql =
            $filtre_sql_fournisseur
            . $filtre_sql_client
            . $filtre_sql_chargement
            . $filtre_sql_livraison
            . $filtre_sql_transporteur
            . $filtre_sql_affreteur;

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
            $filtre_sql
            ORDER BY date_rdv";

        $requete = $this->mysql->prepare($statement);

        $requete->execute([
            "date_debut" => $date_debut,
            "date_fin" => $date_fin
        ]);

        $rdvsRaw = $requete->fetchAll();

        $boisService = new BoisService();

        $rdvs = array_map(
            fn (array $rdvRaw) => $boisService->makeRdvBois($rdvRaw),
            $rdvsRaw
        );

        return $rdvs;
    }

    /**
     * Récupère un RDV bois.
     * 
     * @param int $id ID du RDV à récupérer
     * 
     * @return ?RdvBois Rendez-vous récupéré
     */
    public function getRdv(int $id): ?RdvBois
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
        $rdvRaw = $request->fetch();

        if (!$rdvRaw) return null;

        $boisService = new BoisService();

        $rdv = $boisService->makeRdvBois($rdvRaw);

        return $rdv;
    }

    /**
     * Crée un RDV bois.
     * 
     * @param RdvBois $rdv RDV à créer
     * 
     * @return RdvBois Rendez-vous créé
     */
    public function createRdv(RdvBois $rdv): RdvBois
    {
        $statement = "INSERT INTO bois_planning
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
            'attente' => (int) $rdv->getAttente(),
            'date_rdv' => $rdv->getDate(true),
            'heure_arrivee' => $rdv->getHeureArrivee(true),
            'heure_depart' => $rdv->getHeureDepart(true),
            'chargement' => $rdv->getChargement()->getId(),
            'client' => $rdv->getClient()->getId(),
            'livraison' => $rdv->getLivraison()->getId(),
            'transporteur' => $rdv->getTransporteur()?->getId(),
            'affreteur' => $rdv->getAffreteur()?->getId(),
            'fournisseur' => $rdv->getFournisseur()->getId(),
            'commande_prete' => (int) $rdv->getCommandePrete(),
            'confirmation_affretement' => (int) $rdv->getConfirmationAffretement(),
            'numero_bl' => $rdv->getNumeroBL(),
            'commentaire_public' => $rdv->getCommentairePublic(),
            'commentaire_cache' => $rdv->getCommentaireCache(),
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->getRdv($lastInsertId);
    }

    /**
     * Met à jour un RDV bois.
     * 
     * @param RdvBois $rdv RDV à modifier
     * 
     * @return RdvBois RDV modifié
     */
    public function updateRdv(RdvBois $rdv): RdvBois
    {
        $statement = "UPDATE bois_planning
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
            'attente' => (int) $rdv->getAttente(),
            'date_rdv' => $rdv->getDate(true),
            'heure_arrivee' => $rdv->getHeureArrivee(true),
            'heure_depart' => $rdv->getHeureDepart(true),
            'chargement' => $rdv->getChargement()->getId(),
            'client' => $rdv->getClient()->getId(),
            'livraison' => $rdv->getLivraison()->getId(),
            'transporteur' => $rdv->getTransporteur()?->getId(),
            'affreteur' => $rdv->getAffreteur()?->getId(),
            'fournisseur' => $rdv->getFournisseur()->getId(),
            'commande_prete' => (int) $rdv->getCommandePrete(),
            'confirmation_affretement' => (int) $rdv->getConfirmationAffretement(),
            'numero_bl' => $rdv->getNumeroBL(),
            'commentaire_public' => $rdv->getCommentairePublic(),
            'commentaire_cache' => $rdv->getCommentaireCache(),
            'id' => $rdv->getId(),
        ]);

        return $this->getRdv($rdv->getId());
    }

    /**
     * Met à jour l'état de préparation d'une commande.
     * 
     * @param int   $id     id du RDV à modifier
     * @param array $status Statut de la commande
     * 
     * @return RdvBois RDV modifié
     */
    public function setCommandePrete(int $id, bool $status): RdvBois
    {
        $this->mysql
            ->prepare(
                "UPDATE bois_planning
                SET commande_prete = :commande_prete
                WHERE id = :id"
            )
            ->execute([
                'commande_prete' => (int) $status,
                'id' => $id,
            ]);

        return $this->getRdv($id);
    }

    /**
     * Met à jour l'état de confirmation d'affrètement.
     * 
     * @param int   $id     id du RDV à modifier
     * @param array $status Statut de la confirmation d'affrètement
     * 
     * @return RdvBois RDV modifié
     */
    public function setConfirmationAffretement(int $id, bool $status): RdvBois
    {
        $this->mysql
            ->prepare(
                "UPDATE bois_planning
                SET confirmation_affretement = :confirmation_affretement
                WHERE id = :id"
            )
            ->execute([
                'confirmation_affretement' => (int) $status,
                'id' => $id,
            ]);

        return $this->getRdv($id);
    }

    public function setHeureArrivee(int $id): ?RdvBois
    {
        // Heure
        $heure = date('H:i:s');
        $this->mysql
            ->prepare("UPDATE bois_planning SET heure_arrivee = :heure WHERE id = :id")
            ->execute([
                'heure' => $heure,
                'id' => $id
            ]);


        // Numéro BL automatique (Stora Enso)      
        $rdv = $this->getRdv($id);

        if (!$rdv) return null;

        if (
            $rdv->getFournisseur()?->getId() === 292 /* Stora Enso */
            && $rdv->getChargement()?->getId() === 1 /* AMSB */
        ) {
            // Récupération du numéro de BL du RDV à modifier (si déjà renseigné)
            $reponse_bl_actuel = $this->mysql->prepare(
                "SELECT numero_bl
                FROM bois_planning
                WHERE id = :id"
            );
            $reponse_bl_actuel->execute(["id" => $id]);
            $reponse_bl_actuel = $reponse_bl_actuel->fetch();
            $numero_bl_actuel = $reponse_bl_actuel["numero_bl"];

            // Dernier numéro de BL de Stora Enso :
            // - enregistrement des 10 derniers numéros dans un tableau
            // - tri du tableau
            // - récupération du numéro le plus élevé
            // Ceci permet de prendre en compte les cas où le dernier numéro
            // renseigné n'est pas le plus haut numériquement
            // Permet aussi de prendre en compte les éventuels bons sans numéro "numérique"
            $reponse_bl_precedent = $this->mysql->query(
                "SELECT numero_bl
                FROM bois_planning
                WHERE fournisseur = {$rdv->getFournisseur()->getId()}
                AND numero_bl != ''
                ORDER BY
                    date_rdv DESC,
                    heure_arrivee DESC,
                    numero_bl DESC
                LIMIT 10"
            )->fetchAll();

            $numeros_bl_precedents = [];

            foreach ($reponse_bl_precedent as $numero_bl) {
                // Si le dernier numéro de BL est composé (ex: "200101 + 200102")
                // alors séparation/tri de la chaîne de caractères puis récupération du numéro le plus élevé
                $matches = NULL; // Tableau pour récupérer les numéros de BL
                preg_match_all("/\d{6}/", $numero_bl["numero_bl"], $matches); // Filtre sur les numéros valides (6 chiffres)
                $matches = $matches[0]; // Extraction des résultats
                sort($matches); // Tri des numéros
                $numeros_bl_precedents[] = array_pop($matches); // Récupération du numéro le plus élevé
            }

            // Tri des 10 derniers numéros de BL puis récupération du plus élevé
            sort($numeros_bl_precedents);
            $numero_bl_precedent = array_pop($numeros_bl_precedents);

            // Calcul du nouveau numéro de BL (si possible)
            // Insertion du nouveau numéro de BL si numéro non déjà renseigné
            $numero_bl_nouveau = is_numeric($numero_bl_precedent) ? $numero_bl_precedent + 1 : '';
            if ($numero_bl_actuel === '' && $numero_bl_nouveau) {
                $requete = $this->mysql->prepare(
                    "UPDATE bois_planning
            SET numero_bl = :numero_bl
            WHERE id = :id"
                );

                $requete->execute([
                    'numero_bl' => $numero_bl_nouveau,
                    'id' => $id
                ]);
            }
        }

        return $this->getRdv($id);
    }

    /**
     * Définit l'heure de départ pour un rendez-vous bois.
     *
     * @param int $id L'ID du rendez-vous.
     * 
     * @return RdvBois L'objet de rendez-vous mis à jour.
     */
    public function setHeureDepart(int $id): RdvBois
    {
        $heure = date('H:i:s');
        $this->mysql
            ->prepare("UPDATE bois_planning SET heure_depart = :heure WHERE id = :id")
            ->execute([
                'heure' => $heure,
                'id' => $id
            ]);

        return $this->getRdv($id);
    }

    /**
     * Définit l'heure d'arrivée pour un rendez-vous bois.
     * 
     * @param null|int $id ID du rendez-vous.
     * @param array $input Données du rendez-vous.
     * 
     * @return null|RdvBois Rendez-vous mis à jour.
     */
    public function setNumeroBL(?int $id, array $input): ?RdvBois
    {
        $numero_bl = $input['numero_bl'];
        $fournisseur = [
            "id" => $input['fournisseur'] ?? null,
            "nom" => "",
        ];
        $dry_run = $input["dry_run"] ?? false;

        // Si pas d'identifiant de RDV ni d'identifiant de fournisseur, ne rien faire
        if (!$id && !$fournisseur["id"]) {
            return null;
        }

        $bl_existe = false;

        // Fournisseurs dont le numéro de BL doit être unique
        $fournisseurs_bl_unique = [
            292 // Stora Enso
        ];

        // Si le fournisseur n'est pas dans la liste des fournisseurs dont le numéro de BL doit être unique, ne rien faire
        if ($fournisseur["id"] && !in_array($fournisseur["id"], $fournisseurs_bl_unique)) {
            return null;
        }

        if ($id && !$fournisseur["id"]) {
            $requete_fournisseur = $this->mysql
                ->prepare(
                    "SELECT p.fournisseur as id, f.nom_court AS nom
                            FROM bois_planning p
                            JOIN tiers f ON f.id = p.fournisseur
                            WHERE p.id = :id"
                );
        } else {
            $requete_fournisseur = $this->mysql
                ->prepare(
                    "SELECT t.id, t.nom_court AS nom
                            FROM tiers t
                            WHERE t.id = :id"
                );
        }

        $requete_fournisseur->execute(["id" => $fournisseur["id"] ?? (int) $id]);
        $fournisseur = $requete_fournisseur->fetch();


        // Vérifier si le numéro de BL existe déjà (pour Stora Enso)
        if (
            in_array($fournisseur["id"], $fournisseurs_bl_unique)
            && $numero_bl !== ""
            && $numero_bl !== "-"
        ) {
            $requete = $this->mysql->prepare(
                "SELECT COUNT(id) AS bl_existe, id
                    FROM bois_planning
                    WHERE numero_bl LIKE CONCAT('%', :numero_bl, '%')
                    AND fournisseur = :fournisseur
                    AND NOT id = :id"
            );
            $requete->execute([
                "numero_bl" => $numero_bl,
                "fournisseur" => $fournisseur["id"],
                "id" => (int) $id,
            ]);

            $reponse_bdd = $requete->fetch();

            $bl_existe = (bool) $reponse_bdd["bl_existe"];
        }

        if ($id && !$bl_existe && !$dry_run) {
            $this->mysql
                ->prepare(
                    "UPDATE bois_planning
                        SET numero_bl = :numero_bl
                        WHERE id = :id"
                )
                ->execute([
                    'numero_bl' => $numero_bl,
                    'id' => (int) $id
                ]);
        }

        // Si le numéro de BL existe déjà (pour Stora Enso), message d'erreur
        if ($bl_existe && $id != $reponse_bdd["id"]) {
            throw new ClientException("Le numéro de BL $numero_bl existe déjà pour {$fournisseur["nom"]}.");
        }

        return $id ? $this->getRdv($id) : null;
    }

    /**
     * Supprime un RDV bois.
     * 
     * @param int $id ID du RDV à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteRdv(int $id): bool
    {
        $request = $this->mysql->prepare("DELETE FROM bois_planning WHERE id = :id");
        $success = $request->execute(["id" => $id]);

        if (!$success) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $success;
    }

    /**
     * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
     *
     * @param array $filtre 
     * 
     * @return EntreeRegistreBoisDTO[] Extrait du registre d'affrètement.
     */
    public function getRegistreAffretement(array $filtre): array
    {
        $date_debut_defaut = DateUtils::format(DateUtils::SQL_DATE, DateUtils::previousWorkingDay(new \DateTimeImmutable()));
        $date_fin_defaut = date("Y-m-d");

        // Filtre
        $date_debut = isset($filtre['date_debut'])
            ? ($filtre['date_debut'] ?: $date_debut_defaut)
            : $date_debut_defaut;

        $date_fin = isset($filtre['date_fin'])
            ? ($filtre['date_fin'] ?: $date_fin_defaut)
            : $date_fin_defaut;

        $statement =
            "SELECT
                p.date_rdv,
                f.nom_court AS fournisseur,
                c.nom_court AS chargement_nom,
                c.ville AS chargement_ville,
                cpays.nom AS chargement_pays,
                l.nom_court AS livraison_nom,
                l.cp AS livraison_cp,
                l.ville AS livraison_ville,
                lpays.nom AS livraison_pays,
                p.numero_bl,
                t.nom_court AS transporteur
            FROM bois_planning p
            LEFT JOIN tiers AS c ON p.chargement = c.id
            LEFT JOIN tiers AS l ON p.livraison = l.id
            LEFT JOIN tiers AS a ON p.affreteur = a.id
            LEFT JOIN tiers AS f ON p.fournisseur = f.id
            LEFT JOIN tiers AS t ON p.transporteur = t.id
            LEFT JOIN utils_pays cpays ON c.pays = cpays.iso
            LEFT JOIN utils_pays lpays ON l.pays = lpays.iso
            WHERE a.lie_agence = 1
                AND (date_rdv BETWEEN :date_debut AND :date_fin)
                AND attente = 0
            ORDER BY
            date_rdv,
            numero_bl";

        $requete = $this->mysql->prepare($statement);

        $requete->execute([
            "date_debut" => $date_debut,
            "date_fin" => $date_fin
        ]);

        $rdvsRaw = $requete->fetchAll();

        $boisService = new BoisService();

        $entreesRegistre = array_map(
            fn (array $rdvRaw) => $boisService->makeEntreeRegistreBoisDTO($rdvRaw),
            $rdvsRaw
        );

        return $entreesRegistre;
    }
}
