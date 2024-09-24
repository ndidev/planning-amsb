<?php

namespace App\Models\Admin;

use App\Models\Model;
use App\Core\Auth\AccountStatus;
use App\Core\Auth\User;
use App\Core\Exceptions\Client\Auth\ForbiddenException;

class UserAccountModel extends Model
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

        $users = $this->mysql->query($statement)->fetchAll();

        // Update Redis
        $this->redis->pipeline();
        foreach ($users as $user) {
            $this->redis->hMSet("admin:users:{$user["uid"]}", $user);
        }
        $this->redis->exec();

        // Suppression des comptes ne pouvant pas se connecter
        $users = array_values(array_filter($users, function ($user) {
            return ($user["can_login"] == 1 // comptes TV
                && $user["statut"] !== AccountStatus::DELETED->value // comptes supprimés
            );
        }));

        foreach ($users as &$user) {
            // Suppression des données non nécessaires
            unset($user["password"]);
            unset($user["can_login"]);
            unset($user["login_attempts"]);

            // Rétablissement des types int pour les rôles
            $user["roles"] = json_decode($user["roles"]);
            foreach ($user["roles"] as $role => &$value) {
                $value = (int) $value;
            }
        }
        unset($user);

        return $users;
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
        $user = $this->redis->hGetAll("admin:users:{$uid}");

        // MariaDB
        if (!$user) {
            $statement = "SELECT * FROM admin_users WHERE uid = :uid";

            $request = $this->mysql->prepare($statement);
            $request->execute(["uid" => $uid]);
            $user = $request->fetch();

            if (!$user) return null;

            // Update Redis
            $this->redis->hMSet("admin:users:{$user["uid"]}", $user);
        }


        // Suppression des données non nécessaires
        unset($user["password"]);
        unset($user["can_login"]);
        unset($user["login_attempts"]);

        // Rétablissement des types int pour les rôles
        $user["roles"] = json_decode($user["roles"]);
        foreach ($user["roles"] as $role => &$value) {
            $value = (int) $value;
        }

        return $user;
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
        $uids = $this->mysql->query("SELECT uid FROM admin_users")->fetchAll(\PDO::FETCH_COLUMN);

        do {
            $uid = substr(md5(uniqid()), 0, 8);
        } while (in_array($uid, $uids));

        $statement =
            "INSERT INTO admin_users
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

        $request = $this->mysql->prepare($statement);

        $request->execute([
            "uid" => $uid,
            "login" => substr($input["login"], 0, 255),
            "nom" => substr($input["nom"], 0, 255),
            "statut" => AccountStatus::PENDING->value,
            "roles" => json_encode($input["roles"]),
            "commentaire" => $input["commentaire"],
            "admin" => $this->admin->name
        ]);

        [$uid] = $this->mysql->query("SELECT uid FROM admin_users WHERE login = '{$input["login"]}'")->fetch(\PDO::FETCH_NUM);

        (new User($uid))->updateRedis();

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
        $currentStatus = $user->status;
        $newStatus = AccountStatus::tryFrom($input["statut"]) ?? $user->status;

        // Pas de changement de statut
        if ($currentStatus === $newStatus) {
            $statement =
                "UPDATE admin_users
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

            $request = $this->mysql->prepare($statement);
            $request->execute([
                "login" => substr($input["login"], 0, 255),
                "nom" => substr($input["nom"], 0, 255),
                "commentaire" => $input["commentaire"],
                "roles" => json_encode($input["roles"]),
                "uid" => $uid,
            ]);
        }

        // Passage au statut "PENDING" (réinitialisation)
        if ($currentStatus !== AccountStatus::PENDING && $newStatus === AccountStatus::PENDING) {
            $statement =
                "UPDATE admin_users
                SET
                    password = NULL,
                    statut = :statut,
                    login_attempts = 0,
                    historique = CONCAT(historique, '\n', '(', NOW(), ') Compte réinitialisé par ', :admin)
                WHERE uid = :uid";

            $request = $this->mysql->prepare($statement);
            $request->execute([
                "statut" => AccountStatus::PENDING->value,
                "admin" => $this->admin->name,
                "uid" => $uid
            ]);

            $user->clearSessions();
        }

        // Passage au statut "INACTIVE" (désactivation)
        if ($currentStatus !== AccountStatus::INACTIVE && $newStatus === AccountStatus::INACTIVE) {
            $statement =
                "UPDATE admin_users
                SET
                    password = NULL,
                    statut = :statut,
                    login_attempts = 0,
                    historique = CONCAT(historique, '\n', '(', NOW(), ') Compte désactivé par ', :admin)
                WHERE uid = :uid";

            $request = $this->mysql->prepare($statement);
            $request->execute([
                "statut" => AccountStatus::INACTIVE->value,
                "admin" => $this->admin->name,
                "uid" => $uid
            ]);

            $user->clearSessions();
        }

        $user->updateRedis();

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

        $statement =
            "UPDATE admin_users
            SET
                login = CONCAT(login, '_del-', DATE(NOW()), 'T', TIME(NOW())),
                password = NULL,
                statut = :statut,
                login_attempts = 0,
                historique = CONCAT(historique, '\n', '(', NOW(), ') Compte supprimé par ', :admin)
            WHERE uid = :uid";

        $request = $this->mysql->prepare($statement);
        $success = $request->execute([
            "statut" => AccountStatus::DELETED->value,
            "admin" => $this->admin->name,
            "uid" => $uid
        ]);

        $user->updateRedis();
        $user->clearSessions();

        return $success;
    }
}
