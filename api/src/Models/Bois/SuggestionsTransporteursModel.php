<?php

namespace App\Models\Bois;

use App\Models\Model;

class SuggestionsTransporteursModel extends Model
{
  /**
   * Récupère les transporteurs susceptibles d'effectuer
   * un transport en un lieu de chargement et de livraison.
   * 
   * @param array $filtre Filtre qui contient chargement et livraison
   */
  public function readAll(array $filtre): array
  {
    $chargement_id = $filtre["chargement"];
    $livraison_id = $filtre["livraison"];

    // Récupérer les infos du lieu de chargement et de livraison
    $statement_lieu = "SELECT
        id,
        SUBSTRING(cp, 1, 2) as cp,
        pays
      FROM tiers
      WHERE id = :id";

    $requete_lieu = $this->mysql->prepare($statement_lieu);

    $requete_lieu->execute(["id" => $chargement_id]);
    $donnees_chargement = $requete_lieu->fetch();

    $requete_lieu->execute(["id" => $livraison_id]);
    $donnees_livraison = $requete_lieu->fetch();


    // Récupérer les transporteurs
    // ayant fait des transports identiques ou similaires
    $statement_transporteurs = "SELECT
        COUNT(id) as transports,
        transporteur_nom as nom,
        transporteur_telephone as telephone
      FROM (
        SELECT
          p.id,
          p.date_rdv,
          p.transporteur,
          t.nom_court as transporteur_nom,
          t.telephone as transporteur_telephone,
          p.chargement,
          c.nom_court as c_nom,
          SUBSTRING(c.cp, 1, 2) as c_cp,
          c.pays as c_pays,
          p.livraison,
          l.nom_court as l_nom,
          SUBSTRING(l.cp, 1, 2) as l_cp,
          l.pays as l_pays
        FROM bois_planning p
        JOIN tiers t ON p.transporteur = t.id
        JOIN tiers c ON p.chargement = c.id
        JOIN tiers l ON p.livraison = l.id
        WHERE
              t.actif = 1
          AND t.non_modifiable = 0
        HAVING
            (
                (p.chargement = :chargement_id)
              OR (:chargement_pays = 'FR' AND c_cp = :chargement_cp)
              OR (NOT :chargement_pays = 'FR' AND c_pays = :chargement_pays)
            )
            AND 
            (
                (p.livraison = :livraison_id)
              OR (:livraison_pays = 'FR' AND l_cp = :livraison_cp)
              OR (NOT :livraison_pays = 'FR' AND l_pays = :livraison_pays)
            )
      ) AS transports_corrspondants
      GROUP BY transporteur_nom
      ORDER BY transports DESC
      LIMIT 10";

    $requete_transporteurs = $this->mysql->prepare($statement_transporteurs);

    $requete_transporteurs->execute([
      "chargement_id" => $chargement_id,
      "chargement_cp" => $donnees_chargement["cp"],
      "chargement_pays" => $donnees_chargement["pays"],
      "livraison_id" => $livraison_id,
      "livraison_cp" => $donnees_livraison["cp"],
      "livraison_pays" => $donnees_livraison["pays"],
    ]);

    $donnees_transporteurs = $requete_transporteurs->fetchAll();

    $suggestions = [
      "chargement" => $donnees_chargement,
      "livraison" => $donnees_livraison,
      "transporteurs" => $donnees_transporteurs
    ];

    $donnees = $suggestions;

    return $donnees;
  }
}
