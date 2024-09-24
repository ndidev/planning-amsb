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
        return [
            "uid" => $this->user->uid,
            "login" => $this->user->login,
            "nom" => $this->user->name,
            "roles" => $this->user->roles,
            "statut" => $this->user->status,
        ];
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

        $usernameStatement = "UPDATE `admin_users` SET nom = :nom WHERE `uid` = :uid";

        $passwordStatement = "UPDATE `admin_users` SET `password` = :password WHERE `uid` = :uid";

        $usernameRequest = $this->mysql->prepare($usernameStatement);
        $usernameRequest->execute([
            "nom" => substr($input["nom"], 0, 255),
            "uid" => $uid
        ]);

        if ($input["password"] !== "") {
            $passwordRequest = $this->mysql->prepare($passwordStatement);
            $passwordRequest->execute([
                "password" => password_hash($input["password"], PASSWORD_DEFAULT),
                "uid" => $uid
            ]);
        }

        $this->user->updateRedis();

        $this->user->name = substr($input["nom"], 0, 255);

        return $this->read();
    }
}
