<?php

namespace Api\Utils;

use Api\Utils\User;
use Api\Utils\HTTPRequest;
use Api\Utils\HTTPResponse;

/**
 * Classe servant de base aux contrôleurs.
 * 
 * @package Api\Utils
 */
abstract class BaseController
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
