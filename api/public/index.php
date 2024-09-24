<?php
require_once __DIR__ . "/../bootstrap.php";

use App\Core\Router;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
use App\Controllers\Controller;
use App\Controllers\RootController as Root;
use App\Controllers\Bois\RdvController as RdvBois;
use App\Controllers\Bois\RegistreController as RegistreBois;
use App\Controllers\Bois\StatsController as StatsBois;
use App\Controllers\Bois\SuggestionsTransporteursController as SuggestionsTransporteurs;
use App\Controllers\Vrac\RdvController as RdvVrac;
use App\Controllers\Vrac\ProduitController as VracProduit;
use App\Controllers\Consignation\EscaleController as EscaleConsignation;
use App\Controllers\Consignation\NumVoyageController as NumVoyageConsignation;
use App\Controllers\Consignation\TEController as TE;
use App\Controllers\Consignation\StatsController as StatsConsignation;
use App\Controllers\Consignation\NaviresEnActiviteController as NaviresEnActivite;
use App\Controllers\Consignation\ListeNaviresController as NaviresConsignation;
use App\Controllers\Consignation\ListeMarchandisesController as MarchandisesConsignation;
use App\Controllers\Consignation\ListeClientsController as ClientsConsignation;
use App\Controllers\Chartering\CharterController as AffretementMaritime;
use App\Controllers\Tiers\TiersController as Tiers;
use App\Controllers\Tiers\NombreRdvController as NombreRdv;
use App\Controllers\Utils\PortsController as Ports;
use App\Controllers\Utils\PaysController as Pays;
use App\Controllers\Utils\MareesController as Marees;
use App\Controllers\Config\AgenceController as Agence;
use App\Controllers\Config\BandeauInfoController as BandeauInfo;
use App\Controllers\Config\ConfigPDFController as ConfigPDF;
use App\Controllers\Config\PDF\VisualiserPDFController as VisualiserPDF;
use App\Controllers\Config\PDF\EnvoiPDFController as EnvoiPDF;
use App\Controllers\Config\AjoutRapideController as AjoutRapide;
use App\Controllers\Config\CoteController as Cote;
use App\Controllers\User\UserController as UserManagement;
use App\Controllers\Admin\UserAccountController as UserAccount;
use App\Core\Logger\ErrorLogger;

if (Security::checkIfRequestCanBeDone() === false) {
    (new HTTPResponse(429))
        ->addHeader("Retry-After", (string) Security::BLOCKED_IP_TIMEOUT)
        ->setType("text/plain")
        ->setBody("IP address blocked. Too many unauthenticated requests.")
        ->send();
}

/**
 * Routes de l'API.
 * @var array{{string, string}} $routes
 */
$routes = [
    // Affichage général
    ["/", Root::class],

    // Bois
    ["/bois/rdvs/[i:id]?", RdvBois::class],
    ["/bois/registre", RegistreBois::class],
    ["/bois/stats", StatsBois::class],
    ["/bois/suggestions-transporteurs", SuggestionsTransporteurs::class],

    // Vrac
    ["/vrac/rdvs/[i:id]?", RdvVrac::class],
    ["/vrac/produits/[i:id]?", VracProduit::class],

    // Consignation
    ["/consignation/escales/[i:id]?", EscaleConsignation::class],
    ["/consignation/voyage", NumVoyageConsignation::class],
    ["/consignation/te", TE::class],
    ["/consignation/stats/[*:ids]?", StatsConsignation::class],
    ["/consignation/navires", NaviresConsignation::class],
    ["/consignation/marchandises", MarchandisesConsignation::class],
    ["/consignation/clients", ClientsConsignation::class],
    ["/consignation/navires-en-activite", NaviresEnActivite::class],

    // Chartering
    ["/chartering/charters/[i:id]?", AffretementMaritime::class],

    // Utilitaires
    ["/ports/[a:locode]?", Ports::class],
    ["/pays/[a:iso]?", Pays::class],
    ["/marees/[i:year]?", Marees::class],
    ["/marees/annees", Marees::class],

    // Config
    ["/config/agence/[a:service]?", Agence::class],
    ["/config/bandeau-info/[i:id]?", BandeauInfo::class],
    ["/config/pdf/[i:id]?", ConfigPDF::class],
    ["/config/pdf/visu", VisualiserPDF::class],
    ["/config/pdf/envoi", EnvoiPDF::class],
    ["/config/ajouts-rapides/[i:id]?", AjoutRapide::class],
    ["/config/cotes/[a:cote]?", Cote::class],

    // Tiers
    ["/tiers/[i:id]?", Tiers::class],
    ["/tiers/[i:id]?/nombre_rdv", NombreRdv::class],

    // Admin
    ["/admin/users/[a:uid]?", UserAccount::class],

    // Utilisateur
    ["/user", UserManagement::class],
];

/**
 * @var HTTPResponse $response
 */
$response = null;

/**
 * Routeur
 */
try {
    $router = new Router($routes, $_ENV["API_PATH"]);
    $match = $router->match();

    if (is_array($match)) {
        $controllerClass = $match["target"];
        $params = $match["params"];
        $name = $match["name"];

        /** @var Controller $controller */
        $controller = new $controllerClass(...$params);
    } else {
        // 404 Not Found
        $controller = new Root(true);
    }

    $response = $controller->getResponse();

    $controller->sse->notify();
} catch (ClientException $e) {
    $response = (new HTTPResponse($e->httpStatus))
        ->setType("text")
        ->setBody($e->getMessage());
} catch (ServerException $e) {
    ErrorLogger::log($e);
    $response = (new HTTPResponse($e->httpStatus))
        ->setType("text")
        ->setBody("Erreur serveur");
} catch (\Throwable $e) {
    ErrorLogger::log($e);
    $response = (new HTTPResponse(500))
        ->setType("text")
        ->setBody("Erreur serveur");
}

$response->send();
