<?php

namespace Api\Models\Admin;

use Api\Utils\BaseModel;
use Api\Utils\AccountStatus;
use Api\Utils\User;

class UserAccountResetModel extends BaseModel
{
  /**
   * @param User $admin Compte administrateur effectuant la modification.
   */
  public function __construct(private User $admin)
  {
    parent::__construct();
  }

  /**
   * Récupère un compte utilisateur.
   * 
   * @param int $uid UID du compte à récupérer.
   * 
   * @return array Compte utilisateur récupéré
   */
  public function read(string $uid): array
  {
    $compte = (new UserAccountModel(admin: $this->admin))->read($uid);

    $donnees = $compte;

    return $donnees;
  }


  /**
   * Réinitialise un compte utilisateur.
   * 
   * @param string $uid   UID du compte à réinitialiser
   * 
   * @return array Compte utilisateur modifié
   */
  public function update(string $uid): array
  {
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

    (new User($uid))->update_redis();
    (new User($uid))->clear_sessions();

    return $this->read($uid);
  }
}
