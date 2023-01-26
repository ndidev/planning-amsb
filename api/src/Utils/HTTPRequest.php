<?php

namespace Api\Utils;

class HTTPRequest
{

  /**
   * Méthode HTTP de la requête.
   */
  public string $method;

  /**
   * 
   */
  protected array $headers = [];

  /**
   * ETag de la requête
   */
  public readonly ?string $etag;

  /**
   * Chemin de l'URL.
   */
  public string $path;

  /**
   * Paramètres de la requête (partie après le "?").
   */
  public array $query = [];

  /**
   * Corps de la requête.
   */
  public array $body;

  public function __construct()
  {
    $this->method = $_SERVER["REQUEST_METHOD"];

    $this->headers = getallheaders();

    $url = parse_url($_SERVER['REQUEST_URI']);
    $this->path = $url["path"];
    parse_str($url["query"] ?? "", $this->query);

    $this->body = !empty($_POST) ? $_POST : (array) json_decode(file_get_contents("php://input"), true);

    $this->etag = $this->headers["If-None-Match"] ?? null;
  }
}
