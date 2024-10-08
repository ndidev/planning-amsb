<?php

namespace App\Models\User;

use App\Models\Model;
use App\Core\Auth\User;

class UserModel extends Model
{
    public function __construct(private User $user)
    {
        parent::__construct();
    }

    /**
     * Récupère l'utilisateur courant.
     */
    public function read()
    {
        $donnees = [
            "uid" => $this->user->uid,
            "login" => $this->user->login,
            "nom" => $this->user->nom,
            "roles" => $this->user->roles,
            "statut" => $this->user->statut,
        ];

        return $donnees;
    }

    /**
     * Met à jour l'utilisateur courant.
     * 
     * @param array $input Eléments à modifier
     * 
     * @return array Utilisateur modifié
     */
    public function update(array $input)
    {
        $uid = $this->user->uid;

        $statement_nom = "UPDATE `admin_users`
            SET
                nom = :nom
            WHERE `uid` = :uid";

        $statement_password = "UPDATE `admin_users`
            SET
                `password` = :password
            WHERE `uid` = :uid";

        $requete = $this->mysql->prepare($statement_nom);
        $requete->execute([
            "nom" => substr($input["nom"], 0, 255),
            "uid" => $uid
        ]);

        if ($input["password"] !== "") {
            $requete_password = $this->mysql->prepare($statement_password);
            $requete_password->execute([
                "password" => password_hash($input["password"], PASSWORD_DEFAULT),
                "uid" => $uid
            ]);
        }

        $this->user->update_redis();

        $this->user->nom = substr($input["nom"], 0, 255);

        return $this->read();
    }
}
