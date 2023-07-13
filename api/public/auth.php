<?php

require_once __DIR__ . "/../bootstrap.php";

use Api\Utils\Auth\User;
use Api\Utils\HTTP\HTTPResponse;
use Api\Utils\Exceptions\Auth\AuthException;
use Api\Utils\Exceptions\Auth\AdminException;
use Api\Utils\Exceptions\Auth\AccountPendingException;
use Api\Utils\Exceptions\Auth\AccessException;

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

// Pre-flight request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  (new HTTPResponse())->sendCorsPreflight();
}

// Méthode non supportée
if (array_search($_SERVER["REQUEST_METHOD"], $supported_methods) === FALSE) {
  (new HTTPResponse(501))->send();
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
      switch ($_SERVER["REQUEST_METHOD"]) {
        case "OPTIONS":
          (new HTTPResponse)
            ->setCode(204)
            ->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET")
            ->send();
          break;

        case "GET":
        case "HEAD":
          break;

        default:
          (new HTTPResponse)
            ->setCode(405)
            ->addHeader("Allow", "OPTIONS, HEAD, GET")
            ->send();
          break;
      }


    case 'login':
      if (!isset($_POST["login"]) || !isset($_POST["password"])) {
        (new HTTPResponse(400))->send();
      }

      // Authentification et envoi du cookie
      try {
        $user = (new User)->login($_POST["login"], $_POST["password"]);

        (new HTTPResponse(200))
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
        (new HTTPResponse(200))
          ->setType("json")
          ->setBody(json_encode([
            "message" => $e->getMessage(),
            "statut" => $e->getStatut()
          ]))
          ->send();
      }
      break;


    case 'logout':
      // Suppression de la session et suppression du cookie
      (new User)->logout();
      (new HTTPResponse(204))->send();
      break;


    case 'check':
      // Bypass pour développement
      if ($_ENV["AUTH"] === "OFF") {
        (new HTTPResponse(200))
          ->setType("html")
          ->setBody("Auth OFF")
          ->send();
      }

      $user = (new User)->from_session();

      (new HTTPResponse(200))
        ->setType("json")
        ->setBody(json_encode([
          "login" => $user->login,
          "nom" => $user->nom,
          "roles" => $user->roles,
          "statut" => $user->statut,
        ]))
        ->send();
      break;


    case 'first-login':
      if (!isset($_POST["login"]) || !isset($_POST["password"])) {
        (new HTTPResponse(400))->send();
      }

      (new User)->first_login($_POST["login"], $_POST["password"]);

      (new HTTPResponse(200))->send();
      break;


    case 'info':
      (new HTTPResponse(200))
        ->setType("json")
        ->setBody(json_encode([
          "MAX_LOGIN_ATTEMPTS" => (int) $_ENV["AUTH_MAX_LOGIN_ATTEMPTS"],
          "LONGUEUR_MINI_PASSWORD" => (int) $_ENV["AUTH_LONGUEUR_MINI_PASSWORD"],
        ]))
        ->send();
      break;


      /** DEFAUT */
    default:
      (new HTTPResponse(404))->send();
      break;
  }
} catch (AuthException $e) {
  (new HTTPResponse($e->http_status))
    ->setType("text")
    ->setBody($e->getMessage())
    ->send();
} catch (Throwable $e) {
  error_logger($e);
  (new HTTPResponse(500))
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
