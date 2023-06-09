<?php

namespace Api\Models\Admin;

use Api\Utils\BaseModel;
use Api\Utils\Auth\AccountStatus;
use Api\Utils\Auth\User;
use Api\Utils\Exceptions\Auth\ForbiddenException;

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
  public function readAll(): array
  {
    $statement = "SELECT * FROM admin_users ORDER BY login";

    $comptes = $this->db->query($statement)->fetchAll();

    // Update Redis
    $this->redis->pipeline();
    foreach ($comptes as $compte) {
      $this->redis->hMSet("admin:users:{$compte["uid"]}", $compte);
    }
    $this->redis->exec();

    // Suppression des comptes ne pouvant pas se connecter
    $comptes = array_values(array_filter($comptes, function ($compte) {
      return ($compte["can_login"] == 1 // comptes TV
        && $compte["statut"] !== AccountStatus::DELETED->value // comptes supprimés
      );
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
  public function read(string $uid): ?array
  {
    // Tentative Redis
    $compte = $this->redis->hGetAll("admin:users:{$uid}");

    // MariaDB
    if (!$compte) {
      $statement = "SELECT * FROM admin_users WHERE uid = :uid";

      $requete = $this->db->prepare($statement);
      $requete->execute(["uid" => $uid]);
      $compte = $requete->fetch();

      if (!$compte) return null;

      // Update Redis
      $this->redis->hMSet("admin:users:{$compte["uid"]}", $compte);
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
      "login" => substr($input["login"], 0, 255),
      "nom" => substr($input["nom"], 0, 255),
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
    $user = new User($uid);
    $current_status = $user->statut;
    $new_status = AccountStatus::tryFrom($input["statut"]) ?? $user->statut;

    // Pas de changement de statut
    if ($current_status === $new_status) {
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

      // Conservation du rôle admin : un admin ne peut pas changer lui-même osn statut admin
      if ($user->uid === $this->admin->uid) {
        $input["roles"]["admin"] = $user->roles->admin ?? 0;
      }

      $requete = $this->db->prepare($statement);
      $requete->execute([
        "login" => substr($input["login"], 0, 255),
        "nom" => substr($input["nom"], 0, 255),
        "commentaire" => $input["commentaire"],
        "roles" => json_encode($input["roles"]),
        "uid" => $uid,
      ]);
    }

    // Passage au statut "PENDING" (réinitialisation)
    if ($current_status !== AccountStatus::PENDING && $new_status === AccountStatus::PENDING) {
      $statement = "UPDATE admin_users
        SET
          password = NULL,
          statut = :statut,
          login_attempts = 0,
          historique = CONCAT(historique, '\n', '(', NOW(), ') Compte réinitialisé par ', :admin)
        WHERE uid = :uid";

      $requete = $this->db->prepare($statement);
      $requete->execute([
        "statut" => AccountStatus::PENDING->value,
        "admin" => $this->admin->nom,
        "uid" => $uid
      ]);

      $user->clear_sessions();
    }

    // Passage au statut "INACTIVE" (désactivation)
    if ($current_status !== AccountStatus::INACTIVE && $new_status === AccountStatus::INACTIVE) {
      $statement = "UPDATE admin_users
        SET
          password = NULL,
          statut = :statut,
          login_attempts = 0,
          historique = CONCAT(historique, '\n', '(', NOW(), ') Compte désactivé par ', :admin)
        WHERE uid = :uid";

      $requete = $this->db->prepare($statement);
      $requete->execute([
        "statut" => AccountStatus::INACTIVE->value,
        "admin" => $this->admin->nom,
        "uid" => $uid
      ]);

      $user->clear_sessions();
    }

    $user->update_redis();

    return $this->read($uid);
  }

  /**
   * Supprime un compte utilisateur.
   * 
   * @param string $uid UID du compte à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(string $uid): bool
  {
    $user = new User($uid);

    // Un utilisateur ne peut pas se supprimer lui-même
    if ($user->uid === $this->admin->uid) {
      throw new ForbiddenException("Un administrateur ne peut pas supprimer son propre compte.");
    }

    $statement = "UPDATE admin_users
      SET
        login = CONCAT(login, '_del-', DATE(NOW()), 'T', TIME(NOW())),
        password = NULL,
        statut = :statut,
        login_attempts = 0,
        historique = CONCAT(historique, '\n', '(', NOW(), ') Compte supprimé par ', :admin)
      WHERE uid = :uid";

    $requete = $this->db->prepare($statement);
    $succes = $requete->execute([
      "statut" => AccountStatus::DELETED->value,
      "admin" => $this->admin->nom,
      "uid" => $uid
    ]);

    $user->update_redis();
    $user->clear_sessions();

    return $succes;
  }
}
