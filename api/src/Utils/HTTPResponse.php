<?php

namespace Api\Utils;

require_once __DIR__ . "/../../utils/lzw.inc.php";

/**
 * Réponses HTTP.
 * 
 * Construction des réponses HTTP, avec compression du corps.
 */
class HTTPResponse
{
  private $code = 200;
  private $headers = [];
  private $body = null;
  private $compression = true;
  private $type = 'application/json; charset=UTF-8';
  private $exit = true;
  private $isSent = false;

  public function __construct(?int $code = null)
  {
    if ($code) {
      $this->setCode($code);
    }
  }

  /**
   * Envoi de la réponse HTTP.
   */
  public function send(): void
  {
    if ($this->isSent === true) {
      return;
    }

    if ($this->compression) {
      $this->compressResponse();
    }

    $this->applyStatusCode();
    $this->applyHeaders();

    // Corps de la réponse
    if ($this->body && $_SERVER["REQUEST_METHOD"] !== "HEAD") {
      echo $this->body;
    }

    $this->isSent = true;

    // Sortie de script
    if ($this->exit) {
      exit;
    }
  }

  /**
   * Envoi de la réponse HTTP sans quitter le script.
   * 
   * Permet la continuité de l'exécution après l'envoi.
   */
  public function flush(): void
  {
    @ob_start();

    if ($this->compression) {
      $this->compressResponse();
    }

    $this->applyStatusCode();
    $this->applyHeaders();

    // Corps de la réponse
    if ($this->body && $_SERVER["REQUEST_METHOD"] !== "HEAD") {
      echo $this->body;
    }

    ob_end_flush();
    @ob_flush();
    flush();

    $this->isSent = true;
  }

  /**
   * Debug HTTP response.
   * 
   * @return never
   */
  public function debug()
  {
    echo "<pre>";
    print_r([
      "code" => $this->code,
      "headers" => $this->headers,
      "body" => $this->body,
      "compression" => $this->compression,
      "type" => $this->type,
      "exit" => $this->exit
    ]);
    echo "</pre>";

    exit;
  }

  /**
   * Set HTTP reponse status code.  
   * 
   * The default code is `200`.
   * 
   * @param int $code HTTP status code.
   * 
   * @return HTTPResponse
   */
  public function setCode(int $code): HTTPResponse
  {
    $this->code = $code;

    return $this;
  }

  /**
   * Set HTTP reponse headers.
   * 
   * @param array $headers Array of HTTP headers `[name => value]`.
   * 
   * @return HTTPResponse
   */
  public function setHeaders(array $headers): HTTPResponse
  {
    $this->headers = $headers;

    return $this;
  }

  /**
   * Add a header to the HTTP response.
   * 
   * @param string $name  Name of the header.
   * @param string $value Value of the header.
   * 
   * @return HTTPResponse
   */
  public function addHeader(string $name, string $value): HTTPResponse
  {
    $this->headers[$name] = $value;

    return $this;
  }

  /**
   * Set the body of the HTTP response.
   * 
   * @param mixed $body Body of the HTTP response.
   * 
   * @return HTTPResponse
   */
  public function setBody(mixed $body): HTTPResponse
  {
    $this->body = $body;

    return $this;
  }

  /**
   * Activate or deactivate compression for the HTTP response.  
   * 
   * By default the compression is set to `TRUE`.
   * 
   * @param bool $compression TRUE (activate) or FALSE (deactivate).
   * 
   * @return HTTPResponse
   */
  public function setCompression(bool $compression = true): HTTPResponse
  {
    $this->compression = $compression;

    return $this;
  }

  /**
   * Set the HTTP response body MIME type.
   * 
   * By default, the type is set to `text/html; charset=UTF-8`.
   * 
   * @param string $type MIME type of the body.
   * 
   * @return HTTPResponse
   */
  public function setType(string $type): HTTPResponse
  {
    $type = match ($type) {
      'text', 'plain' => 'text/plain',
      'html' => 'text/html; charset=UTF-8',
      'json' => 'application/json; charset=UTF-8',
      'yaml' => 'application/x-yaml',
      default => $type
    };

    $this->type = $type;

    return $this;
  }

  /**
   * Set if the script must exit after sending the HTTP reponse.
   * 
   * Default is `TRUE`.
   * 
   * @param bool $exit 
   * 
   * @return HTTPResponse
   */
  public function setExit(bool $exit = true): HTTPResponse
  {
    $this->exit = $exit;

    return $this;
  }


  /**
   * Compression du corps de la réponse HTTP.
   * 
   * Vérification des méthodes de compression acceptées.  
   * Compression selon les méthodes acceptées.
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Encoding
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Encoding
   */
  private function compressResponse(): void
  {
    // Si le contenu de la réponse est vide, pas de compression
    if (!$this->body) {
      return;
    }

    // Vérification que la compression est acceptée par le client
    // Enregistrement des méthodes acceptées
    $client_accept_encoding = $_SERVER["HTTP_ACCEPT_ENCODING"] ?? null;

    // Si pas de compression acceptée, renvoi de la réponse intacte
    if ($client_accept_encoding === null) {
      return;
    }

    // Tableau des méthodes accpetées,
    // dans l'ordre de priorité envoyé par le client
    $client_accepted_methods = explode(",", $client_accept_encoding);

    // Création d'un tableau des priorités de compression
    // de la forme ["(string) method" => (number) priority]
    $client_compression_priority = [];

    foreach ($client_accepted_methods as $method) {
      $method_array = explode(";q=", $method);
      $method_name = trim($method_array[0]);
      $method_priority = (float) ($method_array[1] ?? 1);

      $client_compression_priority[$method_name] = $method_priority;
    }

    // Tri du tableau par priorité décroissante
    arsort($client_compression_priority);


    // Méthodes supportées par le serveur
    $server_supported_methods = [
      "gzip" => true,
      "deflate" => true,
      "compress" => true,
      "br" => false, // Voir ci-dessous pour implémentation
      "identity" => true
    ];

    // Méthode utilisée ("identity" par défaut, modifié ci-dessous)
    $compression_method = "identity";

    // Enregistrement de la première méthode acceptée par le client
    // et supportée par le serveur
    foreach ($client_compression_priority as $method => $priority) {
      $is_supported = $server_supported_methods[$method] ?? false;

      if ($is_supported && $priority != 0) {
        $compression_method = $method;
        break;
      }
    }

    // En-tête HTTP
    $this->headers["Content-Encoding"] = $compression_method;

    // Méthodes de compression
    // TODO: gérer les erreurs de compression ? (vraiment nécessaire ?)
    switch ($compression_method) {
      case 'gzip':
        // GZIP (== PHP gzencode)
        $this->body = gzencode($this->body);
        break;

      case 'deflate':
        // HTTP DEFLATE (== PHP gzcompress)
        $this->body = gzcompress($this->body, 9);
        break;

      case 'compress':
        // HTTP Compress (== LZW compress)
        // https://code.google.com/archive/p/php-lzw/
        $this->body = lzw_compress($this->body);
        break;

      case 'br':
        // Brotli
        // Non-implémenté (nécessite compilation du module pour PHP et Apache)
        // TODO: Implémenter Brotli
        // https://github.com/kjdev/php-ext-brotli
        // https://blog.anthony-jacob.com/compiler-le-module-brotli-apache-et-lextension-brotli-php-pour-ubuntu-18-04/

        // $this->body = brotli_compress($this->body);
        break;

      case 'identity':
      default:
        // Identity (pas de compression)
        // Pas de changement du corps de réponse
        break;
    }
  }


  /**
   * Construction des en-têtes de la réponse HTTP.
   * 
   * Définition des en-têtes de base.  
   * Puis définition des en-têtes additionnels passés en paramètre.
   */
  private function applyHeaders(): void
  {
    // En-têtes de base par défaut
    // header("Date: " . gmdate("D, d M Y H:i:s T")); // Temps GMT, désactivé car ajouté par défaut par le serveur web
    header("Access-Control-Allow-Origin: *");
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline'");
    header("Access-Control-Max-Age: 3600");
    header("Cache-control: no-cache");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-API-Key");

    // ! FIXME : Se conformer à la RFC 7230 https://datatracker.ietf.org/doc/html/rfc7230#section-3.3.2
    if (!($this->code < 200 || $this->code === 204)) {
      header("Content-Length: " . strlen($this->body ?? ""));
    }

    // En-tête "Content-Type"
    if ($this->body) {
      header("Content-Type: {$this->type}");
    }


    // Ajout des en-têtes additionnels passés en paramètres aux en-têtes de la réponse
    foreach ($this->headers as $name => $value) {
      header($name ? "$name: $value" : $value);
    }
  }

  /**
   * Application de l'en-tête approprié en fonction du code statut.
   */
  private function applyStatusCode(): void
  {
    match ($this->code) {
      100 => $this->_100_Continue(),
      101 => $this->_101_SwitchingProtocols(),
      103 => $this->_103_EarlyHints(),
      200 => $this->_200_OK(),
      201 => $this->_201_Created(),
      202 => $this->_202_Accepted(),
      203 => $this->_203_NonAuthoritativeInformation(),
      204 => $this->_204_NoContent(),
      205 => $this->_205_ResetContent(),
      206 => $this->_206_PartialContent(),
      301 => $this->_301_MovedPermanently(),
      302 => $this->_302_Found(),
      303 => $this->_303_SeeOther(),
      304 => $this->_304_NotModified(),
      307 => $this->_307_TemporaryRedirect(),
      308 => $this->_308_PermanentRedirect(),
      400 => $this->_400_BadRequest(),
      401 => $this->_401_Unauthorized(),
      402 => $this->_402_PaymentRequired(),
      403 => $this->_403_Forbidden(),
      404 => $this->_404_NotFound(),
      405 => $this->_405_MethodNotAllowed(),
      406 => $this->_406_NotAcceptable(),
      407 => $this->_407_ProxyAuthenticationRequired(),
      408 => $this->_408_RequestTimeout(),
      409 => $this->_409_Conflict(),
      410 => $this->_410_Gone(),
      411 => $this->_411_LengthRequired(),
      412 => $this->_412_PreconditionFailed(),
      413 => $this->_413_PayloadTooLarge(),
      414 => $this->_414_URITooLong(),
      415 => $this->_415_UnsupportedMediaType(),
      416 => $this->_416_RangeNotSatisfiable(),
      417 => $this->_417_ExpectationFailed(),
      418 => $this->_418_Imateapot(),
      422 => $this->_422_UnprocessableEntity(),
      425 => $this->_425_TooEarly(),
      426 => $this->_426_UpgradeRequired(),
      428 => $this->_428_PreconditionRequired(),
      429 => $this->_429_TooManyRequests(),
      431 => $this->_431_RequestHeaderFieldsTooLarge(),
      451 => $this->_451_UnavailableForLegalReasons(),
      500 => $this->_500_InternalServerError(),
      501 => $this->_501_NotImplemented(),
      502 => $this->_502_BadGateway(),
      503 => $this->_503_ServiceUnavailable(),
      504 => $this->_504_GatewayTimeout(),
      505 => $this->_505_HTTPVersionNotSupported(),
      506 => $this->_506_VariantAlsoNegotiates(),
      507 => $this->_507_InsufficientStorage(),
      508 => $this->_508_LoopDetected(),
      510 => $this->_510_NotExtended(),
      511 => $this->_511_NetworkAuthenticationRequired(),
      default => $this->_500_InternalServerError()
    };
  }


  /* ================================ */
  /* == FONCTIONS POUR CHAQUE CODE == */
  /* ================================ */

  /** === 1XX - INFORMATION === */

  /**
   * Réponse 100 (Continue).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/100
   */
  private function _100_Continue(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 100 Continue";
  }

  /**
   * Réponse 101 (Switching Protocols).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/101
   */
  private function _101_SwitchingProtocols(): void
  {
    if ($_SERVER["SERVER_PROTOCOL"] === "HTTP/1.1") {
      $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 101 Switching Protocols";
      // self::$response["Connection"] = "upgrade";
      // self::$response["headers"]["Upgrade"] = null; // Inclure le nouveau protocole dans ce header

    } else {
      $this->_200_OK("");
    }
  }

  /**
   * Réponse 103 (Early Hints).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/103
   */
  private function _103_EarlyHints(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 103 Early Hints";
    // $this->headers["Link"] = null; // En-tête Link à compléter par l'utilisateur

  }


  /** === 2XX - SUCCESS */

  /**
   * Réponse 200 (OK).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/200
   */
  private function _200_OK(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 200 OK";
  }

  /**
   * Réponse 201 (Created).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/201
   */
  private function _201_Created(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 201 Created";
  }

  /**
   * Réponse 202 (Accepted).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/202
   */
  private function _202_Accepted(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 202 Accepted";
  }

  /**
   * Réponse 203 (Non-Authoritative Information).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/203
   */
  private function _203_NonAuthoritativeInformation(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 203 Non-Authoritative Information";
  }

  /**
   * Réponse 204 (No Content).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/204
   */
  private function _204_NoContent(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 204 No Content";
  }

  /**
   * Réponse 205 (Reset Content).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/205
   */
  private function _205_ResetContent(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 205 Reset Content";
  }

  /**
   * Réponse 206 (Partial Content).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/206
   */
  private function _206_PartialContent(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 206 Partial Content";
  }


  /** === 3XX - REDIRECTION MESSAGES */

  /**
   * Réponse 301 (Moved Permamently).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/301
   */
  private function _301_MovedPermanently(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 301 Moved Permanently";
  }

  /**
   * Réponse 302 (Found).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/302
   */
  private function _302_Found(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 302 Found";
  }

  /**
   * Réponse 303 (See Other).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/303
   */
  private function _303_SeeOther(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 303 See Other";
  }

  /**
   * Réponse 304 (Not Modified).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/304
   */
  private function _304_NotModified(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 304 Not Modified";
    // Headers à envoyer : Cache-Control, Content-Location, ETag, Expires, and Vary

  }

  /**
   * Réponse 307 (Temporary Redirect).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/307
   */
  private function _307_TemporaryRedirect(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 307 Temporary Redirect";
  }

  /**
   * Réponse 308 (Permanent Redirect).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/308
   */
  private function _308_PermanentRedirect(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 308 Permanent Redirect";
  }


  /** === 4XX - CLIENT ERRORS === */

  /**
   * Réponse 400 (Bad Request).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400
   */
  private function _400_BadRequest(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request";
  }

  /**
   * Réponse 401 (Unauthorized).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/401
   */
  private function _401_Unauthorized(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 401 Unauthorized";
    // $this->headers["WWW-Authenticate"] = null; // En-tête WWW-Athenticate à renseigner par l'utilisateur

  }

  /**
   * Réponse 402 (Payment Required).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/402
   */
  private function _402_PaymentRequired(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 402 Payment Required";
  }

  /**
   * Réponse 403 (Forbidden).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/403
   */
  private function _403_Forbidden(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden";
  }

  /**
   * Réponse 404 (Not Found).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/404
   */
  private function _404_NotFound(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 404 Not Found";
  }

  /**
   * Réponse 405 (Method Not Allowed).
   * 
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/405
   */
  private function _405_MethodNotAllowed(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 405 Method Not Allowed";
  }

  /**
   * Réponse 406 (Not Acceptable).
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/406
   *  
   * @return array Contenu de la réponse HTTP
   */
  private function _406_NotAcceptable(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 406 Not Acceptable";
  }

  /**
   * Réponse 407 (Proxy Authentication Required)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/407
   */
  private function _407_ProxyAuthenticationRequired(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 407 Proxy Authentication Required";
  }

  /**
   * Réponse 408 (Request Timeout)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/408
   */
  private function _408_RequestTimeout(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 408 Request Timeout";
  }

  /**
   * Réponse 409 (Conflict)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/409
   */
  private function _409_Conflict(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 409 Conflict";
  }

  /**
   * Réponse 410 (Gone)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/410
   */
  private function _410_Gone(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 410 Gone";
  }

  /**
   * Réponse 411 (Length Required)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/411
   */
  private function _411_LengthRequired(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 411 Length Required";
  }

  /**
   * Réponse 412 (Precondition Failed)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/412
   */
  private function _412_PreconditionFailed(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 412 Precondition Failed";
  }

  /**
   * Réponse 413 (Payload Too Large)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/413
   */
  private function _413_PayloadTooLarge(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 413 Payload Too Large";
  }

  /**
   * Réponse 414 (URI Too Long)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/414
   */
  private function _414_URITooLong(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 414 URI Too Long";
  }

  /**
   * Réponse 415 (Unsupported Media Type)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/415
   */
  private function _415_UnsupportedMediaType(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 415 Unsupported Media Type";
  }

  /**
   * Réponse 416 (Range Not Satisfiable)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/416
   */
  private function _416_RangeNotSatisfiable(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 416 Range Not Satisfiable";
  }

  /**
   * Réponse 417 (Expectation Failed)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/417
   */
  private function _417_ExpectationFailed(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 417 Expectation Failed";
  }

  /**
   * Réponse 418 (I'm a teapot)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/418
   */
  private function _418_Imateapot(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 418 I'm a teapot";
  }

  /**
   * Réponse 422 (Unprocessable Entity)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/422
   */
  private function _422_UnprocessableEntity(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 422 Unprocessable Entity";
  }

  /**
   * Réponse 425 (Too Early)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/425
   */
  private function _425_TooEarly(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 425 Too Early";
  }

  /**
   * Réponse 426 (Upgrade Required)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/426
   */
  private function _426_UpgradeRequired(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 426 Upgrade Required";
  }

  /**
   * Réponse 428 (Precondition Required)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/428
   */
  private function _428_PreconditionRequired(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 428 Precondition Required";
  }

  /**
   * Réponse 429 (Too Many Requests)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429
   */
  private function _429_TooManyRequests(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 429 Too Many Requests";
  }

  /**
   * Réponse 431 (Request Header Fields Too Large)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/431
   */
  private function _431_RequestHeaderFieldsTooLarge(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 431 Request Header Fields Too Large";
  }

  /**
   * Réponse 451 (Unavailable For Legal Reasons)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/451
   */
  private function _451_UnavailableForLegalReasons(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 451 Unavailable For Legal Reasons";
  }


  /** === 5XX - SERVER ERRORS === */

  /**
   * Réponse 500 (Internal Server Error)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/500
   */
  private function _500_InternalServerError(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error";
  }

  /**
   * Réponse 501 (Not Implemented)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/501
   */
  private function _501_NotImplemented(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 501 Not Implemented";
  }

  /**
   * Réponse 502 (Bad Gateway)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/502
   */
  private function _502_BadGateway(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 502 Bad Gateway";
  }

  /**
   * Réponse 503 (Service Unavailable)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/503
   */
  private function _503_ServiceUnavailable(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 503 Service Unavailable";
  }

  /**
   * Réponse 504 (Gateway Timeout)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/504
   */
  private function _504_GatewayTimeout(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 504 Gateway Timeout";
  }

  /**
   * Réponse 505 (HTTP Version Not Supported)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/505
   */
  private function _505_HTTPVersionNotSupported(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 505 HTTP Version Not Supported";
  }

  /**
   * Réponse 506 (Variant Also Negotiates)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/506
   */
  private function _506_VariantAlsoNegotiates(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 506 Variant Also Negotiates";
  }

  /**
   * Réponse 507 (Insufficient Storage)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/507
   */
  private function _507_InsufficientStorage(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 507 Insufficient Storage";
  }

  /**
   * Réponse 508 (Loop Detected)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/508
   */
  private function _508_LoopDetected(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 508 Loop Detected";
  }

  /**
   * Réponse 510 (Not Extended)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/510
   */
  private function _510_NotExtended(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 510 Not Extended";
  }

  /**
   * Réponse 511 (Network Authentication Required)
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/511
   */
  private function _511_NetworkAuthenticationRequired(): void
  {
    $this->headers[] = $_SERVER["SERVER_PROTOCOL"] . " 511 Network Authentication Required";
  }
}
