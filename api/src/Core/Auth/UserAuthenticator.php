<?php

// Path: api/src/Core/Auth/UserAuthenticator.php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Array\ArrayHandler;
use App\Core\Array\Environment;
use App\Core\Array\Server;
use App\Core\Component\DateUtils;
use App\Core\Component\SseEventNames;
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
use App\Core\Security;
use App\Entity\User;
use const App\Core\Component\Constants\ONE_WEEK;

final class UserAuthenticator
{
    private User $user;

    private Redis $redis;

    public readonly SSEHandler $sse;

    /**
     * @param null|Redis $redis Pour les tests, une connexion Redis est directement passée en paramètre.
     */
    public function __construct(?Redis $redis = null)
    {
        $this->user = new User();

        $this->redis = $redis ?? new Redis();

        $this->sse = SSEHandler::getInstance();
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

        if ($this->user->canLogin === false) {
            Security::preventBruteforce();

            throw new LoginException();
        }

        // Première connexion (le mot de passe doit avoir été laissé vide)
        if ($this->user->status ===  AccountStatus::PENDING && $password === "") {
            throw new AccountPendingException();
        }

        // Vérification du mot de passe AVANT la vérification du statut
        // de façon à ne pas donner d'informations sur le statut
        // si le mot de passe n'est pas correct
        $isValidPassword = \password_verify($password, $this->user->passwordHash ?? "");

        if (!$isValidPassword) {
            Security::preventBruteforce();

            throw new LoginException();
        }

        // Vérification du statut du compte
        switch ($this->user->status) {
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
                throw new AccountStatusException($this->user->status);
        }

        // Si tout est OK :
        // - mise à jour de la dernière connexion
        // - enregistrement de la session
        // - envoi du cookie

        $now = DateUtils::format(DateUtils::SQL_TIMESTAMP, new \DateTime());

        new MySQL()->query("UPDATE admin_users SET last_connection = '{$now}' WHERE uid = '{$this->user->uid}'");

        $this->redis->hSet("admin:users:{$this->user->uid}", "last_connection", $now);

        $sid = \bin2hex(random_bytes(10));
        $this->registerSession($sid);

        return $this->user;
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

        if ($this->user->status !== AccountStatus::PENDING) {
            throw new AccountStatusException(
                $this->user->status,
                "Le compte n'est pas en attente d'activation"
            );
        }

        new MySQL()->prepareAndExecute(
            "UPDATE `admin_users`
            SET
                `password` = :password,
                `statut` = :statut,
                `login_attempts` = 0,
                `historique` = CONCAT(historique, '\n', '(', NOW(), ') Compte activé')
            WHERE `uid` = :uid",
            [
                "uid" => $this->user->uid,
                "password" => \password_hash($password, PASSWORD_DEFAULT),
                "statut" => AccountStatus::ACTIVE,
            ]
        );

        $this->updateRedis();

        /** @var string $uid */
        $uid = $this->user->uid;

        $this->sse->addEvent(SseEventNames::USER_ACCOUNT, "update", $uid);
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

        if ($this->user->status !== AccountStatus::ACTIVE) {
            throw new AccountStatusException($this->user->status);
        }

        // Prolonger la session
        $this->registerSession($sid);

        return $this->user;
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

        $uid = $apiKeyInfo["uid"] ?? null;
        $status = $apiKeyInfo["status"] ?? null;
        $expiration = $apiKeyInfo["expiration"] ?? null;

        $expirationIsInThePast = true;

        if (null === $expiration || "" === $expiration) {
            $expirationIsInThePast = false;
        }

        try {
            $expirationIsInThePast = \is_string($expiration) && "" !== $expiration && DateUtils::isInThePast($expiration);
        } catch (\Exception) {
            $expirationIsInThePast = true;
        }


        $this->redis->hMSet("admin:apikeys:{$apiKeyHash}", $apiKeyInfo);
        $this->redis->expire("admin:apikeys:{$apiKeyHash}", ONE_WEEK);


        if (!\is_string($uid)) {
            throw new InvalidApiKeyException();
        }

        if ($status !== ApiKeyStatus::ACTIVE) {
            throw new InvalidApiKeyException();
        }

        if ($expirationIsInThePast) {
            throw new InvalidApiKeyException();
        }


        $this->user->uid = $uid;

        // Renseignement des infos de l'utilisateur
        $this->populate();

        if ($this->user->status !== AccountStatus::ACTIVE) {
            throw new AccountStatusException($this->user->status);
        }

        return $this->user;
    }

    /**
     * Met à jours les informations de l'utilisateur dans Redis.
     */
    public function updateRedis(): void
    {
        $userPdoStatement = new MySQL()->query("SELECT * FROM admin_users WHERE uid = '{$this->user->uid}'");

        if (!$userPdoStatement) {
            throw new DBException("Impossible de récupérer les informations de l'utilisateur");
        }

        $user = $userPdoStatement->fetch();

        if (!\is_array($user)) {
            throw new InvalidAccountException();
        }

        $this->redis->hMSet("admin:users:{$this->user->uid}", $user);
    }

    /**
     * Supprimer les sessions de l'utilisateur dans Redis.
     */
    public function clearSessions(string $uid): void
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
        $sessions = \array_combine($sessions, $uids);

        // Supprimer les sessions de l'utilisateur
        $this->redis->pipeline();
        foreach ($sessions as $session => $sessionUid) {
            if ($sessionUid === $uid) {
                $this->redis->del($session);
            }
        }
        $this->redis->exec();

        // Clôturer les connexions SSE
        $this->sse->addEvent(SseEventNames::ADMIN_SESSIONS, "close", "uid:{$uid}");
    }


    /**
     * ===========================================
     *            FONCTIONS PRIVÉES
     * ===========================================
     */


    /**
     * Identifie un utilisateur grâce à son login ou SID.
     * 
     * Si un utilisateur est trouvé, `$this->user->uid` est renseigné.  
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
        if ($this->user->uid) return;

        $uid = NULL;
        $user = false;

        // Identification grâce au login
        if ($login) {
            $user = new MySQL()->prepareAndExecute(
                "SELECT uid FROM admin_users WHERE login = :login",
                ["login" => $login]
            )
                ->fetch();

            if (!\is_array($user) || !\is_string($user["uid"] ?? null)) {
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

        $this->user->uid = $uid;
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
        if (!$this->user->uid) {
            throw new AuthException("Utilisateur non identifié");
        }

        // Tentative Redis
        if (!$this->redis->exists("admin:users:{$this->user->uid}")) {
            $this->updateRedis();
        }

        $user = $this->redis->hGetAll("admin:users:{$this->user->uid}");

        // Prolongation cache
        $this->redis->expire("admin:users:{$this->user->uid}", Environment::getInt('SESSION_EXPIRATION', -1));

        $userAH = new ArrayHandler($user);

        $this->user->login = $userAH->getString('login');
        $this->user->passwordHash = $userAH->getString('password');
        $this->user->canLogin = (bool) $userAH->getBool('can_login');
        $this->user->name = $userAH->getString('nom');
        $this->user->loginAttempts = (int) $userAH->getInt('login_attempts');
        $this->user->status = AccountStatus::from($userAH->getString('statut'));
        $this->user->roles->fillFromJsonString($userAH->getString('roles'));
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
        $this->redis->setex("admin:sessions:{$sid}", Environment::getInt('SESSION_EXPIRATION', -1), $this->user->uid);

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

        $this->sse->addEvent(SseEventNames::ADMIN_SESSIONS, "close", "sid:{$sid}");
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
