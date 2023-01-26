<?php

namespace Api\Models\Config;

use Api\Utils\DatabaseConnector as DB;

class AgenceModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère les données de l'agence.
   * 
   * @return array Données de l'agence
   */
  public function readAll()
  {
    $requete = $this->db->query("SELECT * FROM config_agence");
    $donnees = $requete->fetchAll();

    return $donnees;
  }

  /**
   * Récupère les données d'un service de l'agence.
   * 
   * @param string $service Service de l'agence
   * 
   * @return array Données du service
   */
  public function read(string $service)
  {
    $requete = $this->db->prepare("SELECT * FROM config_agence WHERE service = :service");
    $requete->execute(["service" => $service]);
    $donnees = $requete->fetch();

    return $donnees;
  }

  /**
   * Met à jour les données d'un service de l'agence.
   * 
   * @param string $service Service de l'agence
   * 
   * @return array Données du service
   */
  public function update(string $service, array  $input)
  {
    $statement =
      "UPDATE config_agence
        SET
          nom = :nom,
          adresse_ligne_1 = :adresse_ligne_1,
          adresse_ligne_2 = :adresse_ligne_2,
          cp = :cp,
          ville = :ville,
          pays = :pays,
          telephone = :telephone,
          mobile = :mobile,
          email = :email
        WHERE service = :service";

    $requete = $this->db->prepare($statement);
    $requete->execute([
      "nom" => $input["nom"],
      "adresse_ligne_1" => $input["adresse_ligne_1"],
      "adresse_ligne_2" => $input["adresse_ligne_2"],
      "cp" => $input["cp"],
      "ville" => $input["ville"],
      "pays" => $input["pays"],
      "telephone" => $input["telephone"],
      "mobile" => $input["mobile"],
      "email" => $input["email"],
      "service" => $service,
    ]);

    return $this->read($service);
  }
}
