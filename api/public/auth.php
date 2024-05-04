<?php

require_once __DIR__ . "/../bootstrap.php";

use App\Core\Auth\User;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;
use App\Core\Exceptions\Client\Auth\AccountPendingException;
use App\Core\Exceptions\AppException;


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
 * Supported HTTP methods.
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

// TODO: Properly handle supported methods (CORS)

// Unsupported HTTP method
if (array_search($_SERVER["REQUEST_METHOD"], $supported_methods) === FALSE) {
    (new HTTPResponse(HTTPResponse::HTTP_NOT_IMPLEMENTED_501))->send();
}


// Get the endpoint
$url = parse_url($_SERVER['REQUEST_URI']);
$path = $url["path"] ?? null;
$endpoint = makeEndpoint($path);
$query = [];
parse_str($url["query"] ?? "", $query);

// Endpoints
try {
    switch ($endpoint) {
        case null:
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

            // Authenticate and send the cookie
            try {
                $user = (new User)->login($_POST["login"], $_POST["password"]);

                (new HTTPResponse(HTTPResponse::HTTP_OK_200))
                    ->setType("json")
                    ->setBody(json_encode([
                        "uid" => $user->uid,
                        "login" => $user->login,
                        "nom" => $user->nom,
                        "roles" => $user->roles,
                        "statut" => $user->statut,
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
            // Delete the cookie and session
            (new User)->logout();
            (new HTTPResponse(HTTPResponse::HTTP_NO_CONTENT_204))->send();
            break;


        case '/check':
            // Development bypass
            if ($_ENV["AUTH"] === "OFF") {
                (new HTTPResponse(HTTPResponse::HTTP_OK_200))
                    ->setType("html")
                    ->setBody("Auth OFF")
                    ->send();
            }

            $user = (new User)->from_session();

            (new HTTPResponse(HTTPResponse::HTTP_OK_200))
                ->setType("json")
                ->setBody(json_encode([
                    "login" => $user->login,
                    "nom" => $user->nom,
                    "roles" => $user->roles,
                    "statut" => $user->statut,
                ]))
                ->send();
            break;


        case '/first-login':
            if (!isset($_POST["login"]) || !isset($_POST["password"])) {
                (new HTTPResponse(HTTPResponse::HTTP_NOT_FOUND_404))->send();
            }

            (new User)->first_login($_POST["login"], $_POST["password"]);

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


        default:
            (new HTTPResponse(HTTPResponse::HTTP_NOT_FOUND_404))->send();
            break;
    }
} catch (AppException $e) {
    (new HTTPResponse($e->http_status))
        ->setType("text")
        ->setBody($e->getMessage())
        ->send();
} catch (Throwable $e) {
    error_logger($e);
    (new HTTPResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR_500))
        ->setType("text")
        ->setBody("Erreur serveur")
        ->send();
}


/** === Functions === */

/**
/**
 * Creates the endpoint from the path.
 * 
 * @param string $path Path obtained from parse_url()
 * 
 * @return string Endpoint in the format "path/to/endpoint"
 */
function makeEndpoint(string $path): ?string
{
    if (!$path) {
        return null;
    }

    // Delete the auth path in the request
    // e.g. : "/auth/login" => "/login"
    $auth_path = $_ENV["AUTH_PATH"];
    $endpoint = str_replace($auth_path, "", $path);

    return $endpoint;
}
