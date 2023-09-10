<?php

namespace Api\Models\Tiers;

use Api\Utils\BaseModel;

class NombreRdvModel extends BaseModel
{
  /**
   * Récupère le nombre de RDV pour tous les tiers.
   * 
   * @param array $options Options de récupération
   * 
   * @return array Nombre de RDV pour tous les tiers
   */
  public function readAll(array $options = []): array
  {
    // Nombre de RDV par tiers
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

    $liste_tiers = $this->mysql->query($statement)->fetchAll();

    $liste_tiers_avec_cles = [];
    foreach ($liste_tiers as $infos) {
      if ($infos["nombre_rdv"] > 0) {
        $liste_tiers_avec_cles[$infos["id"]] = $infos["nombre_rdv"];
      }
    }

    $donnees = $liste_tiers_avec_cles;

    return $donnees;
  }

  /**
   * Récupère un tiers.
   * 
   * @param int   $id      ID du tiers à récupérer
   * @param array $options Options de récupération
   * 
   * @return array Tiers récupéré
   */
  public function read($id, array $options = []): array
  {

    /**
     * Requêtes
     */

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
      WHERE t.id = :id
      ";

    $requete = $this->mysql->prepare($statement);
    $requete->execute(["id" => $id]);
    $nombre_rdv = $requete->fetch();

    $donnees = $nombre_rdv;

    return $donnees;
  }
}
