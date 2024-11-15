<?php

// Path: api/src/Core/Auth/User.php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Array\ArrayHandler;
use App\Core\Component\DateUtils;
use App\Core\Component\Module;
use App\Core\Component\SSEHandler;
use App\Core\Database\MySQL;
use App\Core\Database\Redis;
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
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\Array\Environment;
use App\Core\Array\Server;
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
     * 
     * @phpstan-var AccountStatus::* $status
     */
    public string $status;

    /**
     * Rôles de l'utilisateur.
     */
    public readonly UserRoles $roles;

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

        $this->roles = new UserRoles();

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
        // - mise à jour de la dernière connexion
        // - enregistrement de la session
        // - envoi du cookie

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
        $sid = $_COOKIE[Environment::getString('SESSION_COOKIE_NAME')] ?? null;

        if (!\is_string($sid)) {
            return;
        }

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
            throw new AccountStatusException(
                $this->status,
                "Le compte n'est pas en attente d'activation"
            );
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
                "statut" => AccountStatus::ACTIVE,
            ]);

        $this->updateRedis();

        /** @var string $uid */
        $uid = $this->uid;

        $this->sse->addEvent("admin/users", "update", $uid);
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
        $sid = $_COOKIE[Environment::getString('SESSION_COOKIE_NAME')] ?? null;

        if (!\is_string($sid)) {
            throw new SessionException();
        }

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
        $apiKey = Server::getString('HTTP_X_API_KEY', null);

        if (!$apiKey) {
            throw new InvalidApiKeyException();
        }

        $apiKeyHash = \md5($apiKey);

        $apiKeyInfo = $this->redis->hGetAll("admin:apikeys:{$apiKeyHash}");

        if ($apiKeyInfo instanceof \Redis) {
            throw new ServerException("Redis shouldn't be in multimode");
        }

        if (empty($apiKeyInfo)) {
            $keyInfoPdoStatement = (new MySQL())
                ->query("SELECT `uid`, `status`, expiration FROM admin_api_keys WHERE `key` = '{$apiKeyHash}'");

            if (!$keyInfoPdoStatement) {
                throw new DBException("Impossible de récupérer les informations de la clé API");
            }

            $apiKeyInfo = $keyInfoPdoStatement->fetch();

            if (!\is_array($apiKeyInfo)) {
                throw new InvalidApiKeyException();
            }
        }

        if (
            !isset($apiKeyInfo["uid"]) || !\is_string($apiKeyInfo["uid"])
            || !isset($apiKeyInfo["status"]) || !\is_string($apiKeyInfo["status"])
            || !isset($apiKeyInfo["expiration"]) || !\is_string($apiKeyInfo["expiration"])
        ) {
            throw new InvalidApiKeyException();
        }

        $this->redis->hMSet("admin:apikeys:{$apiKeyHash}", $apiKeyInfo);
        $this->redis->expire("admin:apikeys:{$apiKeyHash}", ONE_WEEK);

        [
            "uid" => $uid,
            "status" => $status,
            "expiration" => $expiration
        ] = $apiKeyInfo;


        if (!$uid) {
            throw new InvalidApiKeyException();
        }

        if ($status !== ApiKeyStatus::ACTIVE) {
            throw new InvalidApiKeyException();
        }

        $now = DateUtils::format(DateUtils::SQL_TIMESTAMP, new \DateTime());
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
     * @param ?string $module Rubrique dont l'accès doit être vérifié.
     * 
     * @return bool `true` si l'utilisateur peut accéder à la rubrique, `false` sinon.
     */
    public function canAccess(?string $module): bool
    {
        // Accès à l'accueil et à l'écran individuel de modification du nom/mdp
        if ($module === null || $module === Module::USER) return true;

        return $this->roles->$module >= UserRoles::ACCESS;
    }

    /**
     * Vérifie si l'utilisateur peut éditer une rubrique.
     * 
     * @param ?string $module Rubrique dont l'accès doit être vérifié.
     * 
     * @return bool `true` si l'utilisateur peut éditer la rubrique, `false` sinon.
     */
    public function canEdit(?string $module): bool
    {
        return $this->roles->$module >= UserRoles::EDIT;
    }

    /**
     * `true` si l'utilisateur est administrateur, `false` sinon.
     */
    public function isAdmin(): bool
    {
        return $this->roles->admin >= UserRoles::ACCESS;
    }

    /**
     * Met à jours les informations de l'utilisateur dans Redis.
     */
    public function updateRedis(): void
    {
        $userPdoStatement = (new MySQL())->query("SELECT * FROM admin_users WHERE uid = '{$this->uid}'");

        if (!$userPdoStatement) {
            throw new DBException("Impossible de récupérer les informations de l'utilisateur");
        }

        $user = $userPdoStatement->fetch();

        if (!\is_array($user)) {
            throw new InvalidAccountException();
        }

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
            if ($batch) $sessions = \array_merge($sessions, $batch);
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

            if (!\is_array($user) || !isset($user["uid"])) {
                throw new InvalidAccountException();
            }

            /** @var string */
            $uid = $user["uid"];
        }

        // Identification grâce à l'identifiant de session
        if ($sid) {
            $uid = $this->redis->get("admin:sessions:{$sid}");
            if (!\is_string($uid)) {
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
        $this->redis->expire("admin:users:{$this->uid}", Environment::getInt('SESSION_EXPIRATION', -1));

        $userAH = new ArrayHandler($user);

        $this->login = $userAH->getString('login');
        $this->password = $userAH->getString('password');
        $this->canLogin = (bool) $userAH->getBool('can_login');
        $this->name = $userAH->getString('nom');
        $this->loginAttempts = (int) $userAH->getInt('login_attempts');
        $this->status = AccountStatus::from($userAH->getString('statut'));
        $this->roles->fillFromJsonString($userAH->getString('roles'));
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
        $this->redis->setex("admin:sessions:{$sid}", Environment::getInt('SESSION_EXPIRATION', -1), $this->uid);

        setcookie(Environment::getString('SESSION_COOKIE_NAME'), $sid, [
            "expires" => time() + Environment::getInt('SESSION_EXPIRATION'),
            "path" => Environment::getString('SESSION_COOKIE_PATH'),
            // "samesite" => str_starts_with(Server::getString('HTTP_HOST'), "localhost") ? "None" : "Strict",
            "samesite" => "Strict",
            "secure" => Environment::getString('APP_ENV') !== "development",
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

        setcookie(Environment::getString('SESSION_COOKIE_NAME'), "", [
            "path" => Environment::getString('SESSION_COOKIE_PATH')
        ]);

        $this->sse->addEvent("admin/sessions", "close", "sid:{$sid}");
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
