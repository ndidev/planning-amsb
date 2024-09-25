<?php
require_once __DIR__ . "/../bootstrap.php";

use App\Core\Router;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
use App\Controller\Controller;
use App\Controller\RootController as Root;
use App\Controller\Bois\RdvController as RdvBois;
use App\Controller\Bois\RegistreController as RegistreBois;
use App\Controller\Bois\StatsController as StatsBois;
use App\Controller\Bois\SuggestionsTransporteursController as SuggestionsTransporteurs;
use App\Controller\Vrac\RdvController as RdvVrac;
use App\Controller\Vrac\ProduitController as VracProduit;
use App\Controller\Consignation\EscaleController as EscaleConsignation;
use App\Controller\Consignation\NumVoyageController as NumVoyageConsignation;
use App\Controller\Consignation\TEController as TE;
use App\Controller\Consignation\StatsController as StatsConsignation;
use App\Controller\Consignation\NaviresEnActiviteController as NaviresEnActivite;
use App\Controller\Consignation\ListeNaviresController as NaviresConsignation;
use App\Controller\Consignation\ListeMarchandisesController as MarchandisesConsignation;
use App\Controller\Consignation\ListeClientsController as ClientsConsignation;
use App\Controller\Chartering\CharterController as AffretementMaritime;
use App\Controller\Tiers\TiersController as Tiers;
use App\Controller\Tiers\NombreRdvController as NombreRdv;
use App\Controller\Utils\PortController as Ports;
use App\Controller\Utils\CountryController as Pays;
use App\Controller\Utils\TideController as Marees;
use App\Controller\Config\AgenceController as Agence;
use App\Controller\Config\BandeauInfoController as BandeauInfo;
use App\Controller\Config\ConfigPDFController as ConfigPDF;
use App\Controller\Config\PDF\VisualiserPDFController as VisualiserPDF;
use App\Controller\Config\PDF\EnvoiPDFController as EnvoiPDF;
use App\Controller\Config\AjoutRapideController as AjoutRapide;
use App\Controller\Config\CoteController as Cote;
use App\Controller\User\UserController as UserManagement;
use App\Controller\Admin\UserAccountController as UserAccount;
use App\Core\Logger\ErrorLogger;

if (Security::checkIfRequestCanBeDone() === false) {
    (new HTTPResponse(HTTPResponse::HTTP_TOO_MANY_REQUESTS_429))
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
    $response = (new HTTPResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR_500))
        ->setType("text")
        ->setBody("Erreur serveur");
}

$response->send();
