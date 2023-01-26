<?php
require __DIR__ . "/../bootstrap.php";

use Api\Utils\Router;
use Api\Utils\HTTPResponse;
use Api\Utils\ETag;
use Api\Utils\User;
use Api\Controllers\Bois\RdvController as RdvBois;
use Api\Controllers\Bois\ConfirmationAffretementController as ConfirmationAffretementBois;
use Api\Controllers\Bois\NumeroBLController as NumeroBLBois;
use Api\Controllers\Bois\HeureRDVController as HeureRDVBois;
use Api\Controllers\Bois\RegistreController as RegistreBois;
use Api\Controllers\Bois\StatsController as StatsBois;
use Api\Controllers\Bois\SuggestionsTransporteursController as SuggestionsTransporteurs;
use Api\Controllers\Vrac\RdvController as RdvVrac;
use Api\Controllers\Vrac\ProduitController as VracProduit;
use Api\Controllers\Consignation\EscaleController as EscaleConsignation;
use Api\Controllers\Consignation\NumVoyageController as NumVoyageConsignation;
use Api\Controllers\Consignation\TEController as TE;
use Api\Controllers\Chartering\CharterController as AffretementMaritime;
use Api\Controllers\Tiers\TiersController as Tiers;
use Api\Controllers\Tiers\NombreRdvController as NombreRdv;
use Api\Controllers\Utils\PortsController as Ports;
use Api\Controllers\Utils\PaysController as Pays;
use Api\Controllers\Utils\MareesController as Marees;
use Api\Controllers\Config\ModulesController as Modules;
use Api\Controllers\Config\AgenceController as Agence;
use Api\Controllers\Config\BandeauInfoController as BandeauInfo;
use Api\Controllers\Config\ConfigPDFController as ConfigPDF;
use Api\Controllers\Config\PDF\VisualiserPDFController as VisualiserPDF;
use Api\Controllers\Config\PDF\EnvoiPDFController as EnvoiPDF;
use Api\Controllers\Config\RdvRapidesController as RdvRapides;
use Api\Controllers\Config\CoteController as Cote;
use Api\Controllers\User\UserController as UserManagement;
use Api\Controllers\Admin\UserAccountController as UserAccount;
use Api\Controllers\Admin\UserAccountResetController as UserAccountReset;
use Api\Utils\Exceptions\Auth\AuthException;

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
      (new HTTPResponse(401))->send();
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
 * Méthodes supportées
 * @var string[]
 */
$supported_methods = [
  "OPTIONS",
  "HEAD",
  "GET",
  "POST",
  "PUT",
  "PATCH",
  "DELETE"
];
if (array_search($_SERVER["REQUEST_METHOD"], $supported_methods) === FALSE) {
  (new HTTPResponse(501))->send();
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
  ["/bois/rdvs/[i:id]/confirmation_affretement", fn ($id = null) => new ConfirmationAffretementBois($id)],
  ["/bois/rdvs/[i:id]/numero_bl", fn ($id = null) => new NumeroBLBois($id)],
  ["/bois/rdvs/[i:id]/heure", fn ($id = null) => new HeureRDVBois($id)],
  ["/bois/registre", fn () => new RegistreBois()],
  ["/bois/stats", fn () => new StatsBois()],
  ["/bois/suggestions_transporteurs", fn () => new SuggestionsTransporteurs()],

  // Vrac
  ["/vrac/rdvs/[i:id]?", fn ($id = null) => new RdvVrac($id)],
  ["/vrac/produits/[i:id]?", fn ($id = null) => new VracProduit($id)],

  // Consignation
  ["/consignation/escales/[i:id]?", fn ($id = null) => new EscaleConsignation($id)],
  ["/consignation/voyage", fn () => new NumVoyageConsignation()],
  ["/consignation/te", fn () => new TE()],

  // Chartering
  ["/chartering/charters/[i:id]?", fn ($id = null) => new AffretementMaritime($id)],

  // Utilitaires
  ["/ports/[a:locode]?", fn ($locode = null) => new Ports($locode)],
  ["/pays/[a:iso]?", fn ($iso = null) => new Pays($iso)],
  ["/marees/[i:annee]?", fn ($annee = null) => new Marees($annee)],

  // Config
  ["/config/modules", fn () => new Modules()], // IDEA: ne sert à rien actuellement -> supprimer ?
  ["/config/agence/[a:service]?", fn ($service = null) => new Agence($service)],
  ["/config/bandeau-info/[i:id]?", fn ($id = null) => new BandeauInfo($id)],
  ["/config/pdf/[i:id]?", fn ($id = null) => new ConfigPDF($id)],
  ["/config/pdf/visu", fn () => new VisualiserPDF()],
  ["/config/pdf/envoi", fn () => new EnvoiPDF()],
  ["/config/rdvrapides/[i:id]?", fn ($id = null) => new RdvRapides($id)],
  ["/config/cotes/[a:cote]?", fn ($cote = null) => new Cote($cote)],

  // Tiers
  ["/tiers/[i:id]?", fn ($id = null) => new Tiers($id)],
  ["/tiers/[i:id]?/nombre_rdv", fn ($id = null) => new NombreRdv($id)],

  // Admin
  ["/admin/users/[a:uid]?", fn ($uid = null) => new UserAccount($uid)],
  ["/admin/users/[a:uid]/reset", fn ($uid) => new UserAccountReset($uid)],

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
} catch (AuthException $e) {
  (new HTTPResponse($e->http_status))
    ->setType("text")
    ->setBody($e->getMessage())
    ->send();
} catch (Throwable $e) {
  error_logger($e);
  (new HTTPResponse(500))
    ->setBody(json_encode(["message" => "Erreur serveur"]))
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
    "suggestions_transporteurs" => "suggestions_transporteurs?chargement={id}&livraison={id}",
    // Vrac
    "rdvs_vrac" => "vrac/rdvs/{id}",
    "produits_vrac" => "vrac/produits/{id}",
    // Consignation
    "escales" => "consignation/escales/{id}",
    "escales_archives" => "consignation/escales?archives",
    "te" => "consignation/te",
    // Chartering
    "affretements_maritimes" => "chartering/charters/{id}",
    // Tiers
    "tiers" => "tiers/{id}{?nombre_rdv=true|false}&format={awesomplete}}",
    // Utilitaires
    "pays" => "pays/{iso}",
    "ports" => "ports/{locode}",
    "marees" => "marees/{annee}{?debut={jj/mm/aaaa}&fin={jj/mm/aaaa}",
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
