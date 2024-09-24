<?php

require_once __DIR__ . "/../bootstrap.php";

use App\Core\Auth\User;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;
use App\Core\Exceptions\Client\Auth\AccountPendingException;
use App\Core\Exceptions\AppException;
use App\Core\Logger\ErrorLogger;


if (Security::checkIfRequestCanBeDone() === false) {
    (new HTTPResponse(HTTPResponse::HTTP_TOO_MANY_REQUESTS_429))
        ->addHeader("Retry-After", (string) Security::BLOCKED_IP_TIMEOUT)
        ->setType("text/plain")
        ->setBody("IP address blocked. Too many unauthenticated requests.")
        ->send();
}

// Pre-flight request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    (new HTTPResponse())->sendCorsPreflight();
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
if (array_search($_SERVER["REQUEST_METHOD"], $supported_methods) === FALSE) {
    (new HTTPResponse(HTTPResponse::HTTP_NOT_IMPLEMENTED_501))->send();
}


// Décomposition du chemin
$url = parse_url($_SERVER['REQUEST_URI']);
$path = $url["path"] ?? null;
$endpoint = makeEndpoint($path);
$query = [];
parse_str($url["query"] ?? "", $query);

/**
 * Liste des endoints.
 */
try {
    switch ($endpoint) {
            /** AFFICHAGE GENERAL */
        case null:
        case "":
        case "/":
            switch ($_SERVER["REQUEST_METHOD"]) {
                case "OPTIONS":
                    (new HTTPResponse)
                        ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                        ->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET")
                        ->send();
                    break;

                case "GET":
                case "HEAD":
                    break;

                default:
                    (new HTTPResponse)
                        ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                        ->addHeader("Allow", "OPTIONS, HEAD, GET")
                        ->send();
                    break;
            }


        case '/login':
            if (!isset($_POST["login"]) || !isset($_POST["password"])) {
                (new HTTPResponse(HTTPResponse::HTTP_BAD_REQUEST_400))->send();
            }

            // Authentification et envoi du cookie
            try {
                $user = (new User)->login($_POST["login"], $_POST["password"]);

                (new HTTPResponse(HTTPResponse::HTTP_OK_200))
                    ->setType("json")
                    ->setBody(json_encode([
                        "uid" => $user->uid,
                        "login" => $user->login,
                        "nom" => $user->name,
                        "roles" => $user->roles,
                        "statut" => $user->status,
                    ]))
                    ->send();
            } catch (AccountPendingException $e) {
                (new HTTPResponse(HTTPResponse::HTTP_OK_200))
                    ->setType("json")
                    ->setBody(json_encode([
                        "message" => $e->getMessage(),
                        "statut" => $e->getStatut()
                    ]))
                    ->send();
            }
            break;


        case '/logout':
            // Suppression de la session et suppression du cookie
            (new User)->logout();
            (new HTTPResponse(HTTPResponse::HTTP_NO_CONTENT_204))->send();
            break;


        case '/check':
            // Bypass pour développement
            if ($_ENV["AUTH"] === "OFF") {
                (new HTTPResponse(HTTPResponse::HTTP_OK_200))
                    ->setType("html")
                    ->setBody("Auth OFF")
                    ->send();
            }

            $user = (new User)->identifyFromSession();

            (new HTTPResponse(HTTPResponse::HTTP_OK_200))
                ->setType("json")
                ->setBody(json_encode([
                    "login" => $user->login,
                    "nom" => $user->name,
                    "roles" => $user->roles,
                    "statut" => $user->status,
                ]))
                ->send();
            break;


        case '/first-login':
            if (!isset($_POST["login"]) || !isset($_POST["password"])) {
                (new HTTPResponse(HTTPResponse::HTTP_BAD_REQUEST_400))->send();
            }

            (new User)->initializeAccount($_POST["login"], $_POST["password"]);

            (new HTTPResponse(HTTPResponse::HTTP_OK_200))->send();
            break;


        case '/info':
            (new HTTPResponse(HTTPResponse::HTTP_OK_200))
                ->setType("json")
                ->setBody(json_encode([
                    "MAX_LOGIN_ATTEMPTS" => (int) $_ENV["AUTH_MAX_LOGIN_ATTEMPTS"],
                    "LONGUEUR_MINI_PASSWORD" => (int) $_ENV["AUTH_LONGUEUR_MINI_PASSWORD"],
                ]))
                ->send();
            break;


            /** DEFAUT */
        default:
            (new HTTPResponse(HTTPResponse::HTTP_NOT_FOUND_404))->send();
            break;
    }
} catch (AppException $e) {
    (new HTTPResponse($e->httpStatus))
        ->setType("text")
        ->setBody($e->getMessage())
        ->send();
} catch (Throwable $e) {
    ErrorLogger::log($e);
    (new HTTPResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR_500))
        ->setType("text")
        ->setBody("Erreur serveur")
        ->send();
}


/** === Fonctions === */

/**
 * Crée l'endpoint à partir du path.
 * 
 * @param string $path Path obtenu de parse_url()
 * 
 * @return string Endpoint au format "path/to/endpoint"
 */
function makeEndpoint(string $path): ?string
{
    if (!$path) {
        return null;
    }

    // Suppression du chemin de l'auth dans la requête
    // ex : "/planning-amsb/auth/login" => "login"
    $auth_path = $_ENV["AUTH_PATH"];
    $endpoint = substr_replace($path, "", 0, strlen($auth_path));

    return $endpoint;
}
