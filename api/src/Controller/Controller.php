<?php

namespace App\Controller;

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
  protected readonly HTTPRequest $request;

  /**
   * Réponse HTTP.
   */
  protected readonly HTTPResponse $response;

  /**
   * Supported HTTP methods.
   */
  protected readonly string $supportedMethods;

  /**
   * Server-Sent Events handler.
   */
  public readonly SSEHandler $sse;

  public function __construct(
    string $supportedMethods = "OPTIONS, HEAD, GET",
    bool $error = false
  ) {
    $this->request = new HTTPRequest();
    $this->response = new HTTPResponse();
    $this->sse = new SSEHandler();
    $this->supportedMethods = $supportedMethods;

    if ($error) {
      return;
    }

    $this->processCORSpreflight();

    $this->authenticateUser();
  }

  public function getResponse(): HTTPResponse
  {
    return $this->response;
  }

  /**
   * Process a CORS preflight request.
   */
  private function processCORSpreflight(): void
  {
    if (!$this->request->isPreflight) {
      return;
    }

    $this->response->sendCorsPreflight($this->supportedMethods);
  }

  /**
   * Vérification de l'authentification
   * 2 méthodes : session ou clé API
   * 
   * @throws AuthException
   */
  private function authenticateUser(): void
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
