<?php

// Path: api/public/auth.php

declare(strict_types=1);

require_once __DIR__ . "/../bootstrap.php";

use App\Controller\ErrorController;
use App\Core\Array\Environment;
use App\Core\Array\Server;
use App\Core\Auth\UserAuthenticator;
use App\Core\Exceptions\Client\Auth\AccountPendingException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;


if (Security::checkIfRequestCanBeDone() === false) {
    new HTTPResponse(HTTPResponse::HTTP_TOO_MANY_REQUESTS_429)
        ->addHeader("Retry-After", (string) Security::BLOCKED_IP_TIMEOUT)
        ->setType("text/plain")
        ->setBody("IP address blocked. Too many unauthenticated requests.")
        ->send();
}

// Pre-flight request
if (Server::getString('REQUEST_METHOD') === "OPTIONS") {
    new HTTPResponse()->sendCorsPreflight();
}

/**
 * Méthodes HTTP supportées.
 * @var string[]
 */
$supported_methods = [
    "OPTIONS",
    "HEAD",
    "GET",
    "POST",
    "PUT",
    "PATCH",
    "DELETE"
];

// Méthode non supportée
if (\array_search(Server::getString('REQUEST_METHOD'), $supported_methods) === FALSE) {
    new HTTPResponse(HTTPResponse::HTTP_NOT_IMPLEMENTED_501)->send();
}


// Décomposition du chemin
$requestUri = Server::getString('REQUEST_URI', null);

if (null === $requestUri) {
    throw new ServerException("Request URI not found");
}
$url = \parse_url($requestUri);
$path = $url["path"] ?? null;
$endpoint = makeEndpoint($path);
$query = [];
\parse_str($url["query"] ?? "", $query);

$response = new HTTPResponse();

$userAuthenticator = new UserAuthenticator();

/**
 * Liste des endoints.
 */
try {
    switch ($endpoint) {
        /** AFFICHAGE GENERAL */
        case null:
        case "":
        case "/":
            switch (Server::getString('REQUEST_METHOD')) {
                case "OPTIONS":
                    $response
                        ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                        ->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET");
                    break;

                case "GET":
                case "HEAD":
                    break;

                default:
                    $response
                        ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                        ->addHeader("Allow", "OPTIONS, HEAD, GET");
                    break;
            }


        case '/login':
            if (
                !isset($_POST["login"])
                || !is_string($_POST["login"])
                || !isset($_POST["password"])
                || !is_string($_POST["password"])
            ) {
                throw new BadRequestException("Login et mot de passe requis");
            }

            // Authentification et envoi du cookie
            try {
                $user = $userAuthenticator->login($_POST["login"], $_POST["password"]);

                $response->setJSON([
                    "uid" => $user->uid,
                    "login" => $user->login,
                    "nom" => $user->name,
                    "roles" => $user->roles,
                    "statut" => $user->status,
                ]);
            } catch (AccountPendingException $e) {
                $response->setJSON([
                    "message" => $e->getMessage(),
                    "statut" => $e->getStatus()
                ]);
            }
            break;


        case '/logout':
            // Suppression de la session et suppression du cookie
            $userAuthenticator->logout();
            $response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
            break;


        case '/check':
            // Bypass pour développement
            if (Environment::getString("AUTH") === "OFF") {
                $response->setBody("Auth OFF");
                break;
            }

            $user = $userAuthenticator->identifyFromSession();

            $response->setJSON([
                "login" => $user->login,
                "nom" => $user->name,
                "roles" => $user->roles,
                "statut" => $user->status,
            ]);
            break;


        case '/first-login':
            if (
                !isset($_POST["login"])
                || !is_string($_POST["login"])
                || !isset($_POST["password"])
                || !is_string($_POST["password"])
            ) {
                throw new BadRequestException("Login et mot de passe requis");
            }

            $userAuthenticator->initializeAccount($_POST["login"], $_POST["password"]);

            break;


        case '/info':
            $maxLoginAttempts = Environment::getInt("AUTH_MAX_LOGIN_ATTEMPTS", 0);
            $minPasswordLength = Environment::getInt("AUTH_LONGUEUR_MINI_PASSWORD", 0);

            $response->setJSON([
                "MAX_LOGIN_ATTEMPTS" => $maxLoginAttempts,
                "LONGUEUR_MINI_PASSWORD" => $minPasswordLength,
            ]);
            break;


            /** DEFAUT */
        default:
            $response->setCode(HTTPResponse::HTTP_NOT_FOUND_404);
            break;
    }

    $userAuthenticator->sse->notify();
} catch (\Throwable $e) {
    $response = new ErrorController($e)->getResponse();
} finally {
    $response->send();
}


/** === Fonctions === */

/**
 * Crée l'endpoint à partir du path.
 * 
 * @param ?string $path Path obtenu de parse_url()
 * 
 * @return ?string Endpoint au format "path/to/endpoint"
 */
function makeEndpoint(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    // Suppression du chemin de l'auth dans la requête
    // ex : "/planning-amsb/auth/login" => "login"
    $auth_path = Environment::getString('AUTH_PATH', '/auth');
    $endpoint = substr_replace($path, "", 0, \strlen($auth_path));

    return $endpoint;
}
