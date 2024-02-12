<?php

namespace App\Repository;

use App\Entity\BulkAppointment;
use App\Core\Exceptions\Server\DB\DBException;

class BulkAppointmentRepository extends Repository
{
    /**
     * @var array<int, \App\Entity\BulkAppointment>
     */
    static private array $cache = [];

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function appointmentExists(int $id)
    {
        return $this->mysql->exists("vrac_planning", $id);
    }

    /**
     * Récupère tous les RDV vrac.
     * 
     * @return array<int, \App\Entity\BulkAppointment> Tous les RDV récupérés
     */
    public function getAppointments(): array
    {
        $statement =
            "SELECT
                id,
                date_rdv,
                SUBSTRING(heure, 1, 5) AS heure,
                produit,
                qualite,
                quantite,
                max,
                commande_prete,
                fournisseur,
                client,
                transporteur,
                num_commande,
                commentaire
            FROM vrac_planning
            ORDER BY date_rdv";

        $request = $this->mysql->query($statement);
        $appointmentsRaw = $request->fetchAll();

        $appointments = array_map(
            function (array $appointmentRaw) {
                $appointment = new BulkAppointment($appointmentRaw);

                return $appointment;
            },
            $appointmentsRaw
        );

        return $appointments;
    }

    /**
     * Récupère un RDV vrac.
     * 
     * @param int $id ID du RDV à récupérer
     * 
     * @return ?BulkAppointment Rendez-vous récupéré
     */
    public function getAppointment($id): ?BulkAppointment
    {
        $statement =
            "SELECT
                id,
                date_rdv,
                SUBSTRING(heure, 1, 5) AS heure,
                produit,
                qualite,
                quantite,
                max,
                commande_prete,
                fournisseur,
                client,
                transporteur,
                num_commande,
                commentaire
            FROM vrac_planning
            WHERE id = :id";

        $requete = $this->mysql->prepare($statement);
        $requete->execute(["id" => $id]);
        $appointmentRaw = $requete->fetch();

        if (!$appointmentRaw) return null;

        $appointment = new BulkAppointment($appointmentRaw);

        return $appointment;
    }

    /**
     * Crée un RDV vrac.
     * 
     * @param array $input Eléments du RDV à créer
     * 
     * @return BulkAppointment Rendez-vous créé
     */
    public function createAppointment(array $input): BulkAppointment
    {
        $statement =
            "INSERT INTO vrac_planning
            SET
                date_rdv = :date_rdv,
                heure = :heure,
                produit = :produit,
                qualite = :qualite,
                quantite = :quantite,
                max = :max,
                commande_prete = :commande_prete,
                fournisseur = :fournisseur,
                client = :client,
                transporteur = :transporteur,
                num_commande = :num_commande,
                commentaire = :commentaire
                ";

        $requete = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $requete->execute([
            'date_rdv' => $input["date_rdv"],
            'heure' => $input["heure"] ?: NULL,
            'produit' => $input["produit"],
            'qualite' => $input["qualite"] ?? NULL,
            'quantite' => $input["quantite"],
            'max' => (int) $input["max"],
            'commande_prete' => (int) $input["commande_prete"],
            'fournisseur' => $input["fournisseur"],
            'client' => $input["client"],
            'transporteur' => $input["transporteur"] ?: NULL,
            'num_commande' => $input["num_commande"],
            'commentaire' => $input["commentaire"]
        ]);

        $last_id = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->getAppointment($last_id);
    }

    /**
     * Met à jour un RDV vrac.
     * 
     * @param int   $id ID du RDV à modifier
     * @param array $input  Eléments du RDV à modifier
     * 
     * @return BulkAppointment RDV modifié
     */
    public function updateAppointment($id, array $input): BulkAppointment
    {
        $statement =
            "UPDATE vrac_planning
            SET
                date_rdv = :date_rdv,
                heure = :heure,
                produit = :produit,
                qualite = :qualite,
                quantite = :quantite,
                max = :max,
                commande_prete = :commande_prete,
                fournisseur = :fournisseur,
                client = :client,
                transporteur = :transporteur,
                num_commande = :num_commande,
                commentaire = :commentaire
            WHERE id = :id";

        $requete = $this->mysql->prepare($statement);
        $requete->execute([
            'date_rdv' => $input["date_rdv"],
            'heure' => $input["heure"] ?: NULL,
            'produit' => $input["produit"],
            'qualite' => $input["qualite"] ?? NULL,
            'quantite' => $input["quantite"],
            'max' => (int) $input["max"],
            'commande_prete' => (int) $input["commande_prete"],
            'fournisseur' => $input["fournisseur"],
            'client' => $input["client"],
            'transporteur' => $input["transporteur"] ?: NULL,
            'num_commande' => $input["num_commande"],
            'commentaire' => $input["commentaire"],
            'id' => $id
        ]);

        return $this->getAppointment($id);
    }

    /**
     * Met à jour certaines proriétés d'un RDV vrac.
     * 
     * @param int   $id    id du RDV à modifier
     * @param array $input Données à modifier
     * 
     * @return BulkAppointment RDV modifié
     */
    public function patchAppointment(int $id, array $input): BulkAppointment
    {
        /**
         * Commande prête
         */
        if (isset($input["commande_prete"])) {
            $this->mysql
                ->prepare(
                    "UPDATE vrac_planning
           SET commande_prete = :commande_prete
           WHERE id = :id"
                )
                ->execute([
                    'commande_prete' => (int) $input["commande_prete"],
                    'id' => $id,
                ]);
        }

        return $this->getAppointment($id);
    }

    /**
     * Supprime un RDV vrac.
     * 
     * @param int $id ID du RDV à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteAppointment(int $id): bool
    {
        $requete = $this->mysql->prepare("DELETE FROM vrac_planning WHERE id = :id");
        $succes = $requete->execute(["id" => $id]);

        if (!$succes) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $succes;
    }
}
