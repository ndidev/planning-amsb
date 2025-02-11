<?php

// Path: api/src/Controller/Controller.php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Array\Environment;
use App\Core\Auth\UserAuthenticator;
use App\Core\Component\SSEHandler;
use App\Core\Exceptions\Client\Auth\AuthException;
use App\Core\HTTP\HTTPRequest;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;
use App\Entity\User;

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
    $this->sse = SSEHandler::getInstance();
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
    if (Environment::getString('AUTH') === "ON") {
      $validSession = true;
      $validApiKey = true;

      $userAuthenticator = new UserAuthenticator();

      // Session
      try {
        $this->user = $userAuthenticator->identifyFromSession();
      } catch (AuthException) {
        $validSession = false;
      }

      // Clé API
      try {
        $this->user = $userAuthenticator->identifyFromApiKey();
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
