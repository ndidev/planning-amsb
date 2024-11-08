<?php

namespace App\Core\HTTP;

use App\Core\Exceptions\Server\ServerException;
use App\Core\Logger\ErrorLogger;

/**
 * Réponses HTTP.
 * 
 * Construction des réponses HTTP, avec compression du corps.
 */
final class HTTPResponse
{
    // HTTP status codes constants
    public const HTTP_CONTINUE_100 = 100;
    public const HTTP_SWITCHING_PROTOCOLS_101 = 101;
    public const HTTP_PROCESSING_102 = 102;
    public const HTTP_EARLY_HINTS_103 = 103;
    public const HTTP_OK_200 = 200;
    public const HTTP_CREATED_201 = 201;
    public const HTTP_ACCEPTED_202 = 202;
    public const HTTP_NON_AUTHORITATIVE_INFORMATION_203 = 203;
    public const HTTP_NO_CONTENT_204 = 204;
    public const HTTP_RESET_CONTENT_205 = 205;
    public const HTTP_PARTIAL_CONTENT_206 = 206;
    public const HTTP_MULTI_STATUS_207 = 207;
    public const HTTP_ALREADY_REPORTED_208 = 208;
    public const HTTP_IM_USED_226 = 226;
    public const HTTP_MULTIPLE_CHOICES_300 = 300;
    public const HTTP_MOVED_PERMANENTLY_301 = 301;
    public const HTTP_FOUND_302 = 302;
    public const HTTP_SEE_OTHER_303 = 303;
    public const HTTP_NOT_MODIFIED_304 = 304;
    public const HTTP_USE_PROXY_305 = 305;
    public const HTTP_SWITCH_PROXY_306 = 306;
    public const HTTP_TEMPORARY_REDIRECT_307 = 307;
    public const HTTP_PERMANENT_REDIRECT_308 = 308;
    public const HTTP_BAD_REQUEST_400 = 400;
    public const HTTP_UNAUTHORIZED_401 = 401;
    public const HTTP_PAYMENT_REQUIRED_402 = 402;
    public const HTTP_FORBIDDEN_403 = 403;
    public const HTTP_NOT_FOUND_404 = 404;
    public const HTTP_METHOD_NOT_ALLOWED_405 = 405;
    public const HTTP_NOT_ACCEPTABLE_406 = 406;
    public const HTTP_PROXY_AUTHENTICATION_REQUIRED_407 = 407;
    public const HTTP_REQUEST_TIMEOUT_408 = 408;
    public const HTTP_CONFLICT_409 = 409;
    public const HTTP_GONE_410 = 410;
    public const HTTP_LENGTH_REQUIRED_411 = 411;
    public const HTTP_PRECONDITION_FAILED_412 = 412;
    public const HTTP_PAYLOAD_TOO_LARGE_413 = 413;
    public const HTTP_URI_TOO_LONG_414 = 414;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE_415 = 415;
    public const HTTP_RANGE_NOT_SATISFIABLE_416 = 416;
    public const HTTP_EXPECTATION_FAILED_417 = 417;
    public const HTTP_IM_A_TEAPOT_418 = 418;
    public const HTTP_MISDIRECTED_REQUEST_421 = 421;
    public const HTTP_UNPROCESSABLE_ENTITY_422 = 422;
    public const HTTP_LOCKED_423 = 423;
    public const HTTP_FAILED_DEPENDENCY_424 = 424;
    public const HTTP_TOO_EARLY_425 = 425;
    public const HTTP_UPGRADE_REQUIRED_426 = 426;
    public const HTTP_PRECONDITION_REQUIRED_428 = 428;
    public const HTTP_TOO_MANY_REQUESTS_429 = 429;
    public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE_431 = 431;
    public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS_451 = 451;
    public const HTTP_INTERNAL_SERVER_ERROR_500 = 500;
    public const HTTP_NOT_IMPLEMENTED_501 = 501;
    public const HTTP_BAD_GATEWAY_502 = 502;
    public const HTTP_SERVICE_UNAVAILABLE_503 = 503;
    public const HTTP_GATEWAY_TIMEOUT_504 = 504;
    public const HTTP_HTTP_VERSION_NOT_SUPPORTED_505 = 505;
    public const HTTP_VARIANT_ALSO_NEGOTIATES_506 = 506;
    public const HTTP_INSUFFICIENT_STORAGE_507 = 507;
    public const HTTP_LOOP_DETECTED_508 = 508;
    public const HTTP_NOT_EXTENDED_510 = 510;
    public const HTTP_NETWORK_AUTHENTICATION_REQUIRED_511 = 511;



    private int $code = self::HTTP_OK_200;
    /** @var array<string|int, string> $headers */
    private array $headers = [];
    private ?string $body = null;
    private bool $compression = true;
    private string $type = 'text/html; charset=UTF-8';
    private bool $isSent = false;
    private bool $preflightHeadersAdded = false;

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
        if ($this->body !== null && $_SERVER["REQUEST_METHOD"] !== "HEAD") {
            echo $this->body;
        }

        $this->isSent = true;
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
     * @param ?string $body Body of the HTTP response.
     * 
     * @return HTTPResponse
     */
    public function setBody(?string $body): HTTPResponse
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set the body of the HTTP response as JSON.
     * 
     * @param mixed $data        Data to encode as JSON.
     * @param bool  $alreadyJson Set to true if the data is already JSON encoded.
     * 
     * @return HTTPResponse 
     */
    public function setJSON(mixed $data, bool $alreadyJson = false): HTTPResponse
    {
        $body = $alreadyJson ? $data : json_encode($data);

        if (!is_string($body)) {
            throw new ServerException("Error while encoding data to JSON.");
        }

        $this->body = $body;
        $this->setType('json');

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
            'pdf'  => 'application/pdf',
            'csv'  => 'text/csv',
            default => $type
        };

        $this->type = $type;

        return $this;
    }

    /**
     * Envoyer une réponse à la requête preflight.
     */
    public function sendCorsPreflight(string $supportedMethods = "OPTIONS, HEAD, GET"): void
    {
        $this->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->applyStatusCode();
        $this->addCorsHeaders(true, $supportedMethods);
        $this->applyHeaders();
        exit;
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

        /**
         * Méthodes de compression acceptées par le client.
         * @var ?string
         */
        $clientAcceptEncoding = $_SERVER["HTTP_ACCEPT_ENCODING"] ?? null;

        // Si pas de compression acceptée, renvoi de la réponse intacte
        if ($clientAcceptEncoding === null) {
            return;
        }

        /**
         * Tableau des méthodes accpetées par le client, par ordre de priorité.
         * @var string[]
         */
        $clientAcceptedMethods = explode(",", $clientAcceptEncoding);

        /**
         * Tableau des priorités de compression.  
         * ```
         * [(string) $method => (float) $priority]
         * ```
         * @var array<string, float>
         */
        $clientCompressionPriority = [];

        foreach ($clientAcceptedMethods as $method) {
            $methodArray = explode(";q=", $method);
            $methodName = trim($methodArray[0]);
            $methodPriority = (float) ($methodArray[1] ?? 1);

            $clientCompressionPriority[$methodName] = $methodPriority;
        }

        // Tri du tableau par priorité décroissante
        arsort($clientCompressionPriority);


        /**
         * Méthodes de compression supportées par le serveur.
         * @var bool[]
         */
        $serverSupportedMethods = [
            "gzip" => true,
            "deflate" => true,
            "compress" => false,
            "br" => false, // Voir ci-dessous pour implémentation
            "identity" => true
        ];

        /**
         * Méthode de compression utilisée ("identity" par défaut, modifié ci-dessous).
         * @var string
         */
        $selectedCompressionMethod = "identity";

        // Enregistrement de la première méthode acceptée par le client
        // et supportée par le serveur
        foreach ($clientCompressionPriority as $method => $priority) {
            $isSupportedMethod = $serverSupportedMethods[$method] ?? false;

            if ($isSupportedMethod && $priority != 0) {
                $selectedCompressionMethod = $method;
                break;
            }
        }

        try {
            // Méthodes de compression
            switch ($selectedCompressionMethod) {
                case 'gzip':
                    // GZIP (== PHP gzencode)
                    $compressedBody = gzencode($this->body);

                    // Si la compression est inefficace, on ne compresse pas
                    if ($compressedBody && strlen($compressedBody) >= strlen($this->body)) {
                        $selectedCompressionMethod = "identity";
                        break;
                    }

                    /** @var string $compressedBody */
                    $this->body = $compressedBody;
                    break;

                case 'deflate':
                    // HTTP DEFLATE (== PHP gzcompress)
                    $compressedBody = gzcompress($this->body, 9);

                    // Si la compression est inefficace, on ne compresse pas
                    if ($compressedBody && strlen($compressedBody) >= strlen($this->body)) {
                        $selectedCompressionMethod = "identity";
                        break;
                    }

                    /** @var string $compressedBody */
                    $this->body = $compressedBody;
                    break;

                case 'compress':
                    // HTTP Compress (== LZW compress)
                    // https://code.google.com/archive/p/php-lzw/
                    // Pas trouvé d'implémentation satisfaisante
                    // Compression jamais utilisée
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

            // En-tête HTTP
            $this->headers["Content-Encoding"] = $selectedCompressionMethod;
        } catch (\Throwable $th) {
            ErrorLogger::log($th);

            // Fallback = pas de compression
            // Pas de changement du corps de réponse
        }
    }

    /**
     * Ajouter les en-têtes relatifs à CORS.
     * 
     * @param string $supportedMethods Méthodes HTTP supportées.
     */
    private function addCorsHeaders(bool $is_preflight = false, string $supportedMethods = "OPTIONS, HEAD, GET"): void
    {
        // Pre-flight request
        if ($is_preflight) {
            header("Access-Control-Allow-Methods: " . $supportedMethods);
            header("Access-Control-Allow-Headers: Content-Type, X-API-Key, X-SSE-Connection");
            header("Access-Control-Max-Age: 3600");
        }

        /**
         * Origines acceptées pour CORS.
         * @var string[]
         */
        $allowedOrigins = [
            "https://localhost",
            "http://localhost",
            ($_SERVER["REQUEST_SCHEME"] ?? "") . "://" . explode(":", $_SERVER["HTTP_HOST"])[0],
        ];

        $serverOrigin = $_SERVER["HTTP_ORIGIN"] ?? "";
        $origin = "*";

        foreach ($allowedOrigins as $allowOrigin) {
            if (str_starts_with($serverOrigin, $allowOrigin)) {
                $origin = $serverOrigin;
                break;
            }
        }

        // All requests
        header("Access-Control-Allow-Origin:" . $origin);
        header("Access-Control-Allow-Credentials: true");
        header("Vary: Origin");
    }


    /**
     * Construction des en-têtes de la réponse HTTP.
     * 
     * Définition des en-têtes de base.  
     * Puis définition des en-têtes additionnels passés en paramètre.
     */
    private function applyHeaders(): void
    {
        // Default headers

        // Cache-Control
        header("Cache-control: no-cache");

        // CORS headers
        if ($this->preflightHeadersAdded === false) {
            $this->addCorsHeaders();
        }

        // "Content-Length" header if there is a body
        if ($this->code >= 200 && $this->code !== 204 && $this->code !== 304) {
            header("Content-Length: " . strlen($this->body ?? ""));
        }

        // "Content-Type" header if there is a body
        if ($this->body) {
            header("Content-Type: {$this->type}");
        }

        // Apply customs headers
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
            $this->_200_OK();
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
