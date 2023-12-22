<?php

namespace App\Controllers;

use App\Core\Auth\User;
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

  public function __construct()
  {
    $this->user = $GLOBALS["user"];
    $this->request = new HTTPRequest;
    $this->response = new HTTPResponse;
  }
}
