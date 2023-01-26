<?php

namespace Api\Models\Admin;

use Api\Utils\BaseModel;
use Api\Utils\AccountStatus;
use Api\Utils\User;

class UserAccountModel extends BaseModel
{
  /**
   * @param User $admin Compte administrateur effectuant la modification.
   */
  public function __construct(private User $admin)
  {
    parent::__construct();
  }

  /**
   * Récupère tous les comptes utilisateurs.
   */
  public function readAll()
  {
    $statement = "SELECT * FROM admin_users ORDER BY login";

    $comptes = $this->db->query($statement)->fetchAll();

    // Update Redis
    foreach ($comptes as $compte) {
      foreach ($compte as $key => $value) {
        $this->redis->hSet("users:{$compte["uid"]}", $key, $value);
      }
    }

    // Suppression des comptes TV
    $comptes = array_values(array_filter($comptes, function ($compte) {
      return $compte["can_login"] == 1;
    }));

    foreach ($comptes as &$compte) {
      // Suppression des données non nécessaires
      unset($compte["password"]);
      unset($compte["can_login"]);
      unset($compte["login_attempts"]);

      // Rétablissement des types int pour les rôles
      $compte["roles"] = json_decode($compte["roles"]);
      foreach ($compte["roles"] as $role => &$value) {
        $value = (int) $value;
      }

      // Ajout d'une clé "self" pour désigner l'utilisateur effectuant la requête
      $compte["self"] = $compte["uid"] === $this->admin->uid;
    }
    unset($compte);

    $donnees = $comptes;

    return $donnees;
  }

  /**
   * Récupère un compte utilisateur.
   * 
   * @param int  $uid  UID du compte à récupérer.
   * 
   * @return array Compte utilisateur récupéré
   */
  public function read(string $uid): array|null
  {
    // Tentative Redis
    $compte = $this->redis->hGetAll("users:{$uid}");

    // MariaDB
    if (!$compte) {
      $statement = "SELECT * FROM admin_users WHERE uid = :uid";

      $requete = $this->db->prepare($statement);
      $requete->execute(["uid" => $uid]);
      $compte = $requete->fetch();

      if (!$compte) {
        return NULL;
      }

      // Update Redis
      foreach ($compte as $key => $value) {
        $this->redis->hSet("users:{$compte["uid"]}", $key, $value);
      }
    }


    // Suppression des données non nécessaires
    unset($compte["password"]);
    unset($compte["can_login"]);
    unset($compte["login_attempts"]);

    // Rétablissement des types int pour les rôles
    $compte["roles"] = json_decode($compte["roles"]);
    foreach ($compte["roles"] as $role => &$value) {
      $value = (int) $value;
    }

    // Ajout d'une clé "self" pour désigner l'utilisateur effectuant la requête
    $compte["self"] = $compte["uid"] === $this->admin->uid;

    $donnees = $compte;

    return $donnees;
  }

  /**
   * Crée un compte utilisateur.
   * 
   * @param array $input Eléments du compte à créer.
   * 
   * @return array Compte utilisateur créé
   */
  public function create(array $input): array
  {
    $uids = $this->db->query("SELECT uid FROM admin_users")->fetchAll(\PDO::FETCH_COLUMN);

    do {
      $uid = substr(md5(uniqid()), 0, 8);
    } while (in_array($uid, $uids));

    $statement = "INSERT INTO admin_users
      VALUES(
        :uid,
        :login,
        NULL, -- password
        1,    -- can_login
        :nom,
        NULL, -- created_at
        NULL, -- last_connection
        0,    -- login_attempts
        :statut,
        :roles,
        :commentaire,
        CONCAT('(', NOW(), ') Compte créé par ', :admin) -- historique
      )";

    // Rétablissement des types int pour les rôles
    foreach ($input["roles"] as $role => &$value) {
      $value = (int) $value;
    }

    $requete = $this->db->prepare($statement);

    $requete->execute([
      "uid" => $uid,
      "login" => $input["login"],
      "nom" => $input["nom"],
      "statut" => AccountStatus::PENDING->value,
      "roles" => json_encode($input["roles"]),
      "commentaire" => $input["commentaire"],
      "admin" => $this->admin->nom
    ]);

    [$uid] = $this->db->query("SELECT uid FROM admin_users WHERE login = '{$input["login"]}'")->fetch(\PDO::FETCH_NUM);

    (new User($uid))->update_redis();

    return $this->read($uid);
  }

  /**
   * Met à jour un compte utilisateur.
   * 
   * @param string $uid   UID du compte à modifier
   * @param array  $input Eléments du compte à modifier
   * 
   * @return array Compte utilisateur modifié
   */
  public function update(string $uid, array $input): array
  {
    $statement = "UPDATE admin_users
      SET
        nom = :nom,
        login = :login,
        roles = :roles,
        commentaire = :commentaire
      WHERE uid = :uid";

    // Rétablissement des types int pour les rôles
    foreach ($input["roles"] as $role => &$value) {
      $value = (int) $value;
    }

    $requete = $this->db->prepare($statement);
    $requete->execute([
      "nom" => $input["nom"],
      "login" => $input["login"],
      "commentaire" => $input["commentaire"],
      "roles" => json_encode($input["roles"]),
      "uid" => $uid
    ]);

    (new User($uid))->update_redis();

    return $this->read($uid);
  }

  /**
   * Désactive un compte utilisateur.
   * 
   * @param string $uid UID du compte à désactiver
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(string $uid): bool
  {
    $statement = "UPDATE admin_users
      SET
        statut = :statut,
        password = NULL,
        historique = CONCAT(historique, '\n', '(', NOW(), ') Compte désactivé par ', :admin)
      WHERE uid = :uid";

    $requete = $this->db->prepare($statement);
    $succes = $requete->execute([
      "statut" => AccountStatus::INACTIVE->value,
      "uid" => $uid,
      "admin" => $this->admin->nom
    ]);

    (new User($uid))->update_redis();

    return $succes;
  }
}
