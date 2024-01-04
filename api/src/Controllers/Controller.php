<?php

namespace App\Controllers;

use App\Core\Security;
use App\Core\Auth\User;
use App\Core\Exceptions\Client\Auth\AuthException;
use App\Core\HTTP\HTTPRequest;
use App\Core\HTTP\HTTPResponse;

/**
 * Classe servant de base aux contrôleurs.
 */
abstract class Controller
{
  /**
   * Utilisateur courant.
   */
  protected User $user;

  /**
   * Requête HTTP.
   */
  protected HTTPRequest $request;

  /**
   * Réponse HTTP.
   */
  protected HTTPResponse $response;

  /**
   * En-têtes personnalisés de la réponse HTTP.
   */
  protected array $headers = [];

  /**
   * Supported HTTP methods.
   */
  protected string $supported_methods;

  public function __construct(string $supported_methods = "OPTIONS, HEAD, GET")
  {
    // $this->user = $GLOBALS["user"];
    $this->request = new HTTPRequest;
    $this->response = new HTTPResponse;

    $this->supported_methods = $supported_methods;

    $this->processCORSpreflight();

    $this->authenticateUser();
  }

  /**
   * Process a CORS preflight request.
   */
  private function processCORSpreflight()
  {
    if (!$this->request->is_preflight) {
      return;
    }

    $this->response->sendCorsPreflight($this->supported_methods);
  }

  /**
   * Vérification de l'authentification
   * 2 méthodes : session ou clé API
   */
  private function authenticateUser()
  {
    if ($_ENV["AUTH"] === "ON") {
      try {
        $valid_session = true;
        $valid_api_key = true;

        // Session
        try {
          $this->user = (new User)->from_session();
        } catch (AuthException) {
          $valid_session = false;
        }

        // Clé API
        try {
          $this->user = (new User)->from_api_key();
        } catch (AuthException) {
          $valid_api_key = false;
        }

        // Si aucune des deux authentifications n'est valide
        if (!$valid_session && !$valid_api_key) {
          Security::prevent_bruteforce();

          (new HTTPResponse(401))
            ->setType("text/plain")
            ->setBody("Unauthenticated request.")
            ->send();
        }
      } catch (\Throwable $th) {
        // Autres erreurs non gérées
        error_logger($th);
        (new HTTPResponse(500))
          ->setBody(json_encode(["message" => "Erreur serveur"]))
          ->send();
      }
    }
  }
}
