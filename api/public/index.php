<?php
require __DIR__ . "/../bootstrap.php";

use Api\Utils\Router;
use Api\Utils\HTTP\HTTPResponse;
use Api\Utils\HTTP\ETag;
use Api\Utils\Auth\User;
use Api\Utils\Security;
use Api\Controllers\Bois\RdvController as RdvBois;
use Api\Controllers\Bois\RegistreController as RegistreBois;
use Api\Controllers\Bois\StatsController as StatsBois;
use Api\Controllers\Bois\SuggestionsTransporteursController as SuggestionsTransporteurs;
use Api\Controllers\Vrac\RdvController as RdvVrac;
use Api\Controllers\Vrac\ProduitController as VracProduit;
use Api\Controllers\Consignation\EscaleController as EscaleConsignation;
use Api\Controllers\Consignation\NumVoyageController as NumVoyageConsignation;
use Api\Controllers\Consignation\TEController as TE;
use Api\Controllers\Consignation\StatsController as StatsConsignation;
use Api\Controllers\Consignation\NaviresEnActiviteController as NaviresEnActivite;
use Api\Controllers\Consignation\ListeNaviresController as ListeNavires;
use Api\Controllers\Chartering\CharterController as AffretementMaritime;
use Api\Controllers\Tiers\TiersController as Tiers;
use Api\Controllers\Tiers\NombreRdvController as NombreRdv;
use Api\Controllers\Utils\PortsController as Ports;
use Api\Controllers\Utils\PaysController as Pays;
use Api\Controllers\Utils\MareesController as Marees;
use Api\Controllers\Config\AgenceController as Agence;
use Api\Controllers\Config\BandeauInfoController as BandeauInfo;
use Api\Controllers\Config\ConfigPDFController as ConfigPDF;
use Api\Controllers\Config\PDF\VisualiserPDFController as VisualiserPDF;
use Api\Controllers\Config\PDF\EnvoiPDFController as EnvoiPDF;
use Api\Controllers\Config\AjoutRapideController as AjoutRapide;
use Api\Controllers\Config\CoteController as Cote;
use Api\Controllers\User\UserController as UserManagement;
use Api\Controllers\Admin\UserAccountController as UserAccount;
use Api\Utils\Exceptions\Auth\AuthException;
use Api\Utils\Exceptions\ClientException;

if (Security::check_if_request_can_be_done() === false) {
  (new HTTPResponse(429))
    ->addHeader("Retry-After", (string) Security::BLOCKED_IP_TIMEOUT)
    ->setType("text/plain")
    ->setBody("IP address blocked. Too many unauthenticated requests.")
    ->send();
}

// Pre-flight request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  (new HTTPResponse())->sendCorsPreflight();
}


// Vérification de l'authentification
// 2 méthodes : session ou clé API
if ($_ENV["AUTH"] === "ON") {
  try {
    $valid_session = true;
    $valid_api_key = true;
    $is_admin = false;

    // Session
    try {
      $user = (new User)->from_session();
    } catch (AuthException $e) {
      $valid_session = false;
    }

    // Clé API
    try {
      $user = (new User)->from_api_key();
    } catch (AuthException $e) {
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
  } catch (Throwable $e) {
    // Autres erreurs non gérées
    error_logger($e);
    (new HTTPResponse(500))
      ->setBody(json_encode(["message" => "Erreur serveur"]))
      ->send();
  }
}

/**
 * Routes de l'API.
 * @var array
 */
$routes = [
  // Affichage général
  ["/", fn () => rootResponse()],

  // Bois
  ["/bois/rdvs/[i:id]?", fn ($id = null) => new RdvBois($id)],
  ["/bois/registre", fn () => new RegistreBois()],
  ["/bois/stats", fn () => new StatsBois()],
  ["/bois/suggestions-transporteurs", fn () => new SuggestionsTransporteurs()],

  // Vrac
  ["/vrac/rdvs/[i:id]?", fn ($id = null) => new RdvVrac($id)],
  ["/vrac/produits/[i:id]?", fn ($id = null) => new VracProduit($id)],

  // Consignation
  ["/consignation/escales/[i:id]?", fn ($id = null) => new EscaleConsignation($id)],
  ["/consignation/voyage", fn () => new NumVoyageConsignation()],
  ["/consignation/te", fn () => new TE()],
  ["/consignation/stats", fn () => new StatsConsignation()],
  ["/consignation/navires", fn () => new ListeNavires()],
  ["/consignation/navires-en-activite", fn () => new NaviresEnActivite()],

  // Chartering
  ["/chartering/charters/[i:id]?", fn ($id = null) => new AffretementMaritime($id)],

  // Utilitaires
  ["/ports/[a:locode]?", fn ($locode = null) => new Ports($locode)],
  ["/pays/[a:iso]?", fn ($iso = null) => new Pays($iso)],
  ["/marees/[i:annee]?", fn ($annee = null) => new Marees($annee)],
  ["/marees/annees", fn () => new Marees(annees: true)],

  // Config
  ["/config/agence/[a:service]?", fn ($service = null) => new Agence($service)],
  ["/config/bandeau-info/[i:id]?", fn ($id = null) => new BandeauInfo($id)],
  ["/config/pdf/[i:id]?", fn ($id = null) => new ConfigPDF($id)],
  ["/config/pdf/visu", fn () => new VisualiserPDF()],
  ["/config/pdf/envoi", fn () => new EnvoiPDF()],
  ["/config/ajouts-rapides/[i:id]?", fn ($id = null) => new AjoutRapide($id)],
  ["/config/cotes/[a:cote]?", fn ($cote = null) => new Cote($cote)],

  // Tiers
  ["/tiers/[i:id]?", fn ($id = null) => new Tiers($id)],
  ["/tiers/[i:id]?/nombre_rdv", fn ($id = null) => new NombreRdv($id)],

  // Admin
  ["/admin/users/[a:uid]?", fn ($uid = null) => new UserAccount($uid)],

  // Utilisateur
  ["/user", fn () => new UserManagement()],
];

/**
 * Routeur
 */
try {
  $router = new Router($routes, $_ENV["API_PATH"]);
  $match = $router->match();

  if (is_array($match)) {
    $controller = $match["target"];
    $params = $match["params"];
    call_user_func_array($controller, $params);
  } else {
    $body = buildIndex();
    (new HTTPResponse(404))->setBody(json_encode($body))->send();
  }
} catch (AuthException | ClientException $e) {
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
 * Construit la liste des URL disponibles pour l'API.
 * 
 * @return string[] Liste des URL disponibles pour l'API.
 */
function buildIndex(): array
{
  $liste_endpoints = [
    // Bois
    "rdvs_bois" => "bois/rdvs/{id}{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&client={client}&livraison={livraison}&fournisseur={fournisseur}&affreteur={affreteur}&transporteur={transporteur}}",
    "registre" => "bois/registre/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}",
    "stats" => "bois/stats/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&client={client}&livraison={livraison}&fournisseur={fournisseur}&affreteur={affreteur}&transporteur={transporteur}}",
    "suggestions-transporteurs" => "suggestions-transporteurs?chargement={id}&livraison={id}",
    // Vrac
    "rdvs_vrac" => "vrac/rdvs/{id}",
    "produits_vrac" => "vrac/produits/{id}",
    // Consignation
    "escales" => "consignation/escales/{id}",
    "escales_archives" => "consignation/escales?archives",
    "te" => "consignation/te",
    "stats" => "consignation/stats/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&armateur={armateur}}",
    "navires" => "consignation/navires",
    "navires-en-activite" => "consignation/navires-en-activite/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}",
    // Chartering
    "affretements_maritimes" => "chartering/charters/{id}",
    // Tiers
    "tiers" => "tiers/{id}{?nombre_rdv=true|false}&format={awesomplete}}",
    // Utilitaires
    "pays" => "pays/{iso}",
    "ports" => "ports/{locode}",
    "marees" => "marees/{annee}{?debut={jj/mm/aaaa}&fin={jj/mm/aaaa}",
    "marees_annees" => "marees/annees",
    // Config
    "modules" => "config/modules",
    "bandeau-info" => "config/bandeau/{id}",
    "pdf_configs" => "config/pdf",
    "pdf_visu" => "config/pdf/visu/{?module={module}&fournisseur={fournisseur}&date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}}",
    "pdf_envoi" => "config/pdf/envoi/",
    "rdvrapides" => "config/rdvrapides/{id}",
    "agence" => "config/agence/{service}",
    "cotes" => "config/cotes/{cote}",
    // Utilisateur
    "user" => "user/",
    // Administration
    "users" => "admin/users/{uid}",
  ];

  foreach ($liste_endpoints as $description => $path) {
    $liste_endpoints[$description] = $_ENV["API_URL"] . "/" . $path;
  }

  return $liste_endpoints;
}

/**
 * Affiche la liste des url disponibles pour l'API.
 */
function showIndex()
{
  $liste_endpoints = buildIndex();

  $etag = ETag::get($liste_endpoints);

  if (isset($_SERVER["HTTP_IF_NONE_MATCH"]) && $etag === $_SERVER["HTTP_IF_NONE_MATCH"]) {
    (new HTTPResponse(304))->send();
  }

  (new HTTPResponse)
    ->addHeader("ETag", $etag)
    ->setType("json")
    ->setBody(json_encode($liste_endpoints))
    ->send();
}


/**
 * Réponse à appliquer en cas d'appel à l'endpoint "/".
 */
function rootResponse(): void
{
  switch ($_SERVER["REQUEST_METHOD"]) {
    case "OPTIONS":
      (new HTTPResponse)
        ->setCode(204)
        ->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET")
        ->send();
      break;

    case "GET":
    case "HEAD":
      showIndex();
      break;

    default:
      (new HTTPResponse)
        ->setCode(405)
        ->addHeader("Allow", "OPTIONS, HEAD, GET")
        ->send();
      break;
  }
}
