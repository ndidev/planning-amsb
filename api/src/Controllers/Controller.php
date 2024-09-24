<?php

namespace App\Controllers;

use App\Core\Auth\User;
use App\Core\Component\SSEHandler;
use App\Core\Exceptions\Client\Auth\AuthException;
use App\Core\HTTP\HTTPRequest;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;

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
  protected string $supportedMethods;

  /**
   * Server-Sent Events handler.
   */
  public SSEHandler $sse;

  public function __construct(string $supportedMethods = "OPTIONS, HEAD, GET")
  {
    // $this->user = $GLOBALS["user"];
    $this->request = new HTTPRequest();
    $this->response = new HTTPResponse();

    $this->supportedMethods = $supportedMethods;

    $this->sse = new SSEHandler();

    $this->processCORSpreflight();

    $this->authenticateUser();
  }

  abstract function processRequest();

  public function getResponse(): HTTPResponse
  {
    return $this->response;
  }

  /**
   * Process a CORS preflight request.
   */
  private function processCORSpreflight()
  {
    if (!$this->request->isPreflight) {
      return;
    }

    $this->response->sendCorsPreflight($this->supportedMethods);
  }

  /**
   * Vérification de l'authentification
   * 2 méthodes : session ou clé API
   */
  private function authenticateUser()
  {
    if ($_ENV["AUTH"] === "ON") {
      $validSession = true;
      $validApiKey = true;

      // Session
      try {
        $this->user = (new User)->identifyFromSession();
      } catch (AuthException) {
        $validSession = false;
      }

      // Clé API
      try {
        $this->user = (new User)->identifyFromApiKey();
      } catch (AuthException) {
        $validApiKey = false;
      }

      // Si aucune des deux authentifications n'est valide
      if (!$validSession && !$validApiKey) {
        Security::preventBruteforce();

        throw new AuthException("Unauthenticated request.");
      }
    }
  }
}
