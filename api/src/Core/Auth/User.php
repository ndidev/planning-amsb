<?php

namespace App\Core\Auth;

use App\Core\Component\Module;
use App\Core\Component\SSEHandler;
use App\Core\Database\MySQL;
use App\Core\Database\Redis;
use App\Core\Component\DateUtils;
use App\Core\Exceptions\Client\Auth\AccountDeletedException;
use App\Core\Exceptions\Client\Auth\AccountInactiveException;
use App\Core\Exceptions\Client\Auth\AccountLockedException;
use App\Core\Exceptions\Client\Auth\AccountPendingException;
use App\Core\Exceptions\Client\Auth\AccountStatusException;
use App\Core\Exceptions\Client\Auth\AuthException;
use App\Core\Exceptions\Client\Auth\InvalidAccountException;
use App\Core\Exceptions\Client\Auth\InvalidApiKeyException;
use App\Core\Exceptions\Client\Auth\LoginException;
use App\Core\Exceptions\Client\Auth\MaxLoginAttemptsException;
use App\Core\Exceptions\Client\Auth\SessionException;

use App\Core\Security;
use const App\Core\Component\Constants\ONE_WEEK;

/**
 * Classe contenant toutes les propriétés d'un compte utilisateur.
 * 
 * @package App\Core
 */
class User
{
    /**
     * UID de l'utilisateur.
     */
    public ?string $uid = null;

    /**
     * Identifiant de l'utilisateur.
     */
    public ?string $login = null;

    /**
     * Hash du mot de passe.
     */
    public ?string $password;

    /**
     * `true` si le compte peut être utilisé pour se connecter (utilisateur normal).  
     * `false` si le compte ne peut pas être utilisé pour se connecter (ex: compte "kiosque" type Raspberry Pi). 
     */
    public bool $canLogin;

    /**
     * Nom de l'utilisateur.
     */
    public string $name;

    /**
     * Nombre de tentatives de connexion échouées.
     */
    public int $loginAttempts;

    /**
     * Statut du compte de l'utilisateur.
     */
    public AccountStatus|string $status;

    /**
     * Rôles de l'utilisateur.
     */
    public object $roles;

    /**
     * `true` si l'utilisateur est administrateur, `false` sinon.
     */
    public readonly bool $isAdmin;

    private Redis $redis;

    private SSEHandler $sse;

    public function __construct(?string $uid = null, ?Redis $redis = null)
    {
        if ($redis) {
            // Utilisé pour les tests, une connexion Redis est directement passée en paramètre
            $this->redis = $redis;
        } else {
            $this->redis = new Redis();
        }

        if ($uid) {
            $this->uid = $uid;
            $this->populate();
        }

        $this->sse = new SSEHandler();
    }

    /**
     * Connecte un utilisateur et enregistre sa session.
     * 
     * Nettoie également les sessions expirées et oubliées.
     * 
     * @param string $login    Identifiant de l'utilisateur.
     * @param string $password Mot de passe de l'utilisateur.
     * 
     * @return User Informations utilisateur.
     * 
     * @throws LoginException
     * @throws AccountStatusException
     * @throws MaxLoginAttemptsException
     */
    public function login(string $login, string $password): User
    {
        try {
            $this->identify(login: $login);
        } catch (InvalidAccountException) {
            Security::preventBruteforce();

            throw new LoginException();
        }

        $this->populate();

        if ($this->canLogin === false) {
            Security::preventBruteforce();

            throw new LoginException();
        }

        // Première connexion (le mot de passe doit avoir été laissé vide)
        if ($this->status ===  AccountStatus::PENDING && $password === "") {
            throw new AccountPendingException();
        }

        // Vérification du mot de passe AVANT la vérification du statut
        // de façon à ne pas donner d'informations sur le statut
        // si le mot de passe n'est pas correct
        $isValidPassword = password_verify($password, $this->password ?? "");

        if (!$isValidPassword) {
            Security::preventBruteforce();

            throw new LoginException();
        }

        // Vérification du statut du compte
        switch ($this->status) {
            case AccountStatus::ACTIVE:
                break;

            case AccountStatus::PENDING:
                throw new AccountPendingException();

            case AccountStatus::INACTIVE:
                throw new AccountInactiveException();

            case AccountStatus::LOCKED:
                throw new AccountLockedException();

            case AccountStatus::DELETED:
                throw new AccountDeletedException();

            default:
                throw new AccountStatusException($this->status);
        }

        // Si tout est OK :
        // - remise à zéro des tentatives de connexions
        // - mise à jour de la dernière connexion
        // - enregistrement de la session
        // - envoi du cookie
        $this->resetLoginAttempts();

        $now = DateUtils::format(DateUtils::SQL_TIMESTAMP, new \DateTime());

        (new MySQL)
            ->query("UPDATE admin_users SET last_connection = '{$now}' WHERE uid = '{$this->uid}'");

        $this->redis->hSet("admin:users:{$this->uid}", "last_connection", $now);

        $sid = bin2hex(random_bytes(10));
        $this->registerSession($sid);

        return $this;
    }

    /**
     * Déconnecte un utilisateur.
     */
    public function logout(): void
    {
        if (!isset($_COOKIE[$_ENV["SESSION_COOKIE_NAME"]])) {
            return;
        }

        $sid = $_COOKIE[$_ENV["SESSION_COOKIE_NAME"]];

        $this->deleteSession($sid);
    }

    /**
     * Initialise un mot de passe utilisateur (première connexion).
     * 
     * @throws AccountStatusException
     */
    public function initializeAccount(string $login, string $password): void
    {
        $this->identify(login: $login);

        $this->populate();

        if ($this->status !== AccountStatus::PENDING) {
            throw new AccountStatusException($this->status, "Le compte n'est pas en attente d'activation");
        }

        (new MySQL)
            ->prepare(
                "UPDATE `admin_users`
                SET
                    `password` = :password,
                    `statut` = :statut,
                    `login_attempts` = 0,
                    `historique` = CONCAT(historique, '\n', '(', NOW(), ') Compte activé')
                WHERE `uid` = :uid"
            )
            ->execute([
                "uid" => $this->uid,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                // "statut" => AccountStatus::ACTIVE->value
                "statut" => AccountStatus::ACTIVE,
            ]);

        $this->updateRedis();

        $this->sse->addEvent("admin/users", "update", $this->uid);
    }


    /**
     * Identifie et remplit les informations d'un utilisateur
     * d'après son identifiant de session.
     * 
     * @return User 
     * 
     * @throws SessionException 
     * @throws AccountStatusException 
     * @throws \RedisException 
     */
    public function identifyFromSession(): User
    {
        if (!isset($_COOKIE[$_ENV["SESSION_COOKIE_NAME"]])) {
            throw new SessionException();
        }

        $sid = $_COOKIE[$_ENV["SESSION_COOKIE_NAME"]];

        try {
            $this->identify(sid: $sid);
        } catch (SessionException $e) {
            // Si la session n'existe plus, supprimer le cookie
            $this->deleteSession($sid);
            throw $e;
        }

        // Renseignement des infos de l'utilisateur
        $this->populate();

        if ($this->status !== AccountStatus::ACTIVE) {
            throw new AccountStatusException($this->status);
        }

        // Prolonger la session
        $this->registerSession($sid);

        return $this;
    }

    /**
     * Identifie et remplit les informations d'un utilisateur d'après une clé API.
     * 
     * @return User
     * 
     * @throws InvalidApiKeyException
     * @throws \RedisException
     */
    public function identifyFromApiKey(): User
    {
        $api_key = $_SERVER["HTTP_X_API_KEY"] ?? null;

        if (!$api_key) {
            throw new InvalidApiKeyException();
        }

        $api_key_hash = md5($api_key);

        $key_info =
            $this->redis->hGetAll("admin:apikeys:{$api_key_hash}")
            ?: (new MySQL)
            ->query("SELECT `uid`, `status`, expiration FROM admin_api_keys WHERE `key` = '{$api_key_hash}'")
            ->fetch();

        if ($key_info) {
            $this->redis->hMSet("admin:apikeys:{$api_key_hash}", $key_info);
            $this->redis->expire("admin:apikeys:{$api_key_hash}", ONE_WEEK);
        }

        [
            "uid" => $uid,
            "status" => $status,
            "expiration" => $expiration
        ] = $key_info;


        if (!$uid) {
            Security::preventBruteforce();
            throw new InvalidApiKeyException();
        }

        if ($status !== ApiKeyStatus::ACTIVE) {
            Security::preventBruteforce();
            throw new InvalidApiKeyException();
        }

        $now = DateUtils::format(DateUtils::SQL_TIMESTAMP, new \DateTime);
        if ($expiration && $expiration < $now) {
            throw new InvalidApiKeyException();
        }


        $this->uid = $uid;

        // Renseignement des infos de l'utilisateur
        $this->populate();

        if ($this->status !== AccountStatus::ACTIVE) {
            throw new AccountStatusException($this->status);
        }

        return $this;
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une rubrique.
     * 
     * @param ?Module Rubrique dont l'accès doit être vérifié.
     * 
     * @return bool `true` si l'utilisateur peut accéder à la rubrique, `false` sinon.
     */
    public function canAccess(?Module $module): bool
    {
        // Accès à l'accueil et à l'écran individuel de modification du nom/mdp
        if ($module === null || $module === Module::USER) return true;

        $moduleName = $module->value;

        return ($this->roles->$moduleName ?? -1) >= UserRoles::ACCESS->value;
    }

    /**
     * Vérifie si l'utilisateur peut éditer une rubrique.
     * 
     * @param ?Module Rubrique dont l'accès doit être vérifié.
     * 
     * @return bool `true` si l'utilisateur peut éditer la rubrique, `false` sinon.
     */
    public function canEdit(?Module $module): bool
    {
        $moduleName = $module->value;

        return ($this->roles->$moduleName ?? -1) >= UserRoles::EDIT->value;
    }

    /**
     * Met à jours les informations de l'utilisateur dans Redis.
     */
    public function updateRedis(): void
    {
        $user =
            (new MySQL)
            ->query("SELECT * FROM admin_users WHERE uid = '{$this->uid}'")
            ->fetch();

        // Copie des infos dans Redis (hash)
        $this->redis->hMSet("admin:users:{$this->uid}", $user);
    }

    /**
     * Supprimer les sessions de l'utilisateur dans Redis.
     */
    public function clearSessions(): void
    {
        // Obtenir toutes les sessions en cours
        $sessions = [];
        do {
            $batch = $this->redis->scan($iterator, "admin:sessions:*");
            if ($batch) $sessions = array_merge($sessions, $batch);
        } while ($iterator);

        // Obtenir les utilisateurs pour chaque session
        $this->redis->pipeline();
        foreach ($sessions as $session) {
            $this->redis->get($session);
        }
        $uids = $this->redis->exec();

        // Combiner sessions et utilisateurs
        $sessions = array_combine($sessions, $uids);

        // Supprimer les sessions de l'utilisateur
        $this->redis->pipeline();
        foreach ($sessions as $session => $uid) {
            if ($uid === $this->uid) {
                $this->redis->del($session);
            }
        }
        $this->redis->exec();

        // Clôturer les connexions SSE
        $this->sse->addEvent("admin/sessions", "close", "uid:{$this->uid}");
    }


    /**
     * ===========================================
     *            FONCTIONS PRIVÉES
     * ===========================================
     */


    /**
     * Identifie un utilisateur grâce à son login ou SID.
     * 
     * Si un utilisateur est trouvé, `$this->uid` est renseigné.  
     * 
     * Un des 2 paramètres doit être fourni.
     * 
     * @param ?string $login Login de l'utilisateur.
     * @param ?string $sid   Identifiant de session.
     * 
     * @throws InvalidAccountException 
     * @throws SessionException 
     */
    private function identify(?string $login = null, ?string $sid = null): void
    {
        // Si déjà identifié, ne pas exécuter la fonction
        if ($this->uid) return;

        $uid = NULL;
        $user = false;

        // Identification grâce au login
        if ($login) {
            $mysql = new MySQL();
            $requete = $mysql->prepare("SELECT uid FROM admin_users WHERE login = :login");
            $requete->execute(["login" => $login]);
            $user = $requete->fetch();
            $uid = $user["uid"] ?? null;

            if (!$uid) {
                throw new InvalidAccountException();
            }
        }

        // Identification grâce à l'identifiant de session
        if ($sid) {
            $uid = $this->redis->get("admin:sessions:{$sid}");
            if ($uid === false) {
                throw new SessionException();
            }
        }

        $this->uid = $uid;
    }

    /**
     * Remplit les informations de l'objet utlisateur.
     * 
     * En profite pour remplir les informations du compte dans Redis
     * si elles n'y sont pas déjà.
     *
     * @throws InvalidAccountException Si le compte n'existe pas.
     */
    private function populate(): void
    {
        if (!$this->uid) {
            throw new AuthException("Utilisateur non identifié");
        }

        // Tentative Redis
        if (!$this->redis->exists("admin:users:{$this->uid}")) {
            $this->updateRedis();
        }

        $user = $this->redis->hGetAll("admin:users:{$this->uid}");

        // Prolongation cache
        $this->redis->expire("admin:users:{$this->uid}", $_ENV["SESSION_EXPIRATION"]);

        $this->login = $user["login"];
        $this->password = $user["password"];
        $this->canLogin = $user["can_login"];
        $this->name = $user["nom"];
        $this->loginAttempts = $user["login_attempts"];
        $this->status = AccountStatus::tryFrom($user["statut"]) ?? $user["statut"];
        $this->roles = json_decode($user["roles"]);
        $this->isAdmin = (bool) ($this->roles?->admin ?? false);
    }

    /**
     * Enregistre ou prolonge une session.
     * 
     * Enregistre la session dans Redis
     * et crée/prolonge un cookie de session.
     * 
     * @param string $sid ID de la session.
     * 
     * @throws \RedisException
     */
    private function registerSession(string $sid): void
    {
        $this->redis->setex("admin:sessions:{$sid}", $_ENV["SESSION_EXPIRATION"], $this->uid);

        setcookie($_ENV["SESSION_COOKIE_NAME"], $sid, [
            "expires" => time() + $_ENV["SESSION_EXPIRATION"],
            "path" => $_ENV["SESSION_COOKIE_PATH"],
            // "samesite" => str_starts_with($_SERVER['HTTP_HOST'], "localhost") ? "None" : "Strict",
            "samesite" => "Strict",
            "secure" => $_ENV["APP_ENV"] !== "development",
            // "secure" => true,
            "httponly" => true
        ]);
    }

    /**
     * Supprime une session.
     * 
     * Supprime la session de Redis
     * et passe la valeur du cookie de session à `false`.
     * 
     * @throws \RedisException
     */
    private function deleteSession(string $sid): void
    {
        $this->redis->del("admin:sessions:{$sid}");

        setcookie($_ENV["SESSION_COOKIE_NAME"], false, [
            "path" => $_ENV["SESSION_COOKIE_PATH"]
        ]);

        $this->sse->addEvent("admin/sessions", "close", "sid:{$sid}");
    }

    /**
     * Incrémente le nombre de tentatives de connexions de l'utilisateur.
     *
     * @return int Nombre de tentatives de connexion.
     *
     * @throws \PDOException 
     * @throws \RedisException 
     */
    private function incrementLoginAttempts(): int
    {
        (new MySQL)
            ->query(
                "UPDATE admin_users
          SET login_attempts = login_attempts + 1
          WHERE uid = '{$this->uid}'"
            );

        $login_attempts = $this->redis->hIncrBy("admin:users:{$this->uid}", "login_attempts", 1);

        return $login_attempts;
    }

    /**
     * Réinitialise le nombre de tentatives de connexions pour un utilisateur.
     * 
     * @throws \PDOException 
     * @throws \RedisException 
     */
    private function resetLoginAttempts(): void
    {
        (new MySQL)
            ->query("UPDATE admin_users SET login_attempts = 0 WHERE uid = '{$this->uid}'");

        $this->redis->hSet("admin:users:{$this->uid}", "login_attempts", 0);
    }

    /**
     * Bloque le compte d'un utilisateur.
     * 
     * @param string $raison Raison du blocage du compte.
     * 
     * @throws \PDOException 
     * @throws \RedisException 
     */
    private function lockAccount(string $raison = ""): void
    {
        // Si le compte est déjà bloqué, ne rien faire
        if ($this->status === AccountStatus::LOCKED) {
            return;
        }

        (new MySQL)
            ->prepare(
                "UPDATE admin_users
          SET
            statut = :statut,
            historique = CONCAT(historique, '\n', :raison)
          WHERE uid = :uid"
            )
            ->execute([
                "statut" => AccountStatus::LOCKED->value,
                "raison" => $raison,
                "uid" => $this->uid
            ]);

        $this->redis->hMSet(
            "admin:users:{$this->uid}",
            [
                "statut" => AccountStatus::LOCKED->value,
                "historique" => $this->redis->hGet("admin:users:{$this->uid}", "historique") . PHP_EOL . $raison
            ]
        );

        // Notification SSE
        $user = $this->redis->hGetAll("admin:users:{$this->uid}");
        unset($user["password"]);
        unset($user["can_login"]);
        unset($user["login_attempts"]);
        // Rétablissement des types int pour les rôles
        $user["roles"] = json_decode($user["roles"]);
        foreach ($user["roles"] as $role => &$value) {
            $value = (int) $value;
        }
        $this->sse->addEvent("admin/users", "update", $this->uid, $user);

        $this->clearSessions();
    }

    /**
     * =================================
     *           MAGIC MATHODS
     * =================================
     */

    public function __destruct()
    {
        $this->redis->close();
    }
}
