<?php
require_once __DIR__ . "/../bootstrap.php";

use App\Core\Router;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
use App\Controller\Controller;
use App\Controller\RootController;
use App\Controller\Timber\TimberAppointmentController;
use App\Controller\Timber\TimberRegistryController;
use App\Controller\Timber\TimberStatsController;
use App\Controller\Timber\TransportSuggestionsController;
use App\Controller\Bulk\BulkAppointmentController;
use App\Controller\Bulk\BulkProductController;
use App\Controller\Consignation\EscaleController;
use App\Controller\Consignation\NumVoyageController;
use App\Controller\Consignation\TEController;
use App\Controller\Consignation\StatsConsignationController;
use App\Controller\Consignation\NaviresEnActiviteController;
use App\Controller\Consignation\ListeNaviresConsignationController;
use App\Controller\Consignation\ListeMarchandisesConsignationController;
use App\Controller\Consignation\ListeClientsConsignationController;
use App\Controller\Chartering\CharterController;
use App\Controller\ThirdParty\ThirdPartyController;
use App\Controller\ThirdParty\AppointmentCountController;
use App\Controller\Utils\PortController;
use App\Controller\Utils\CountryController;
use App\Controller\Utils\TideController;
use App\Controller\Config\AgenceController;
use App\Controller\Config\BandeauInfoController;
use App\Controller\Config\ConfigPDFController;
use App\Controller\Config\PDF\VisualiserPDFController;
use App\Controller\Shipping\ShippingCallController;
use App\Controller\User\UserController;
use App\Controller\Admin\UserAccountController;
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
    ["/", RootController::class],

    // Bois
    ["/bois/rdvs/[i:id]?", TimberAppointmentController::class],
    ["/bois/registre", TimberRegistryController::class],
    ["/bois/stats", TimberStatsController::class],
    ["/bois/suggestions-transporteurs", TransportSuggestionsController::class],

    // Vrac
    ["/vrac/rdvs/[i:id]?", BulkAppointmentController::class],
    ["/vrac/produits/[i:id]?", BulkProductController::class],

    // Consignation
    ["/consignation/escales/[i:id]?", ShippingCallController::class],
    ["/consignation/voyage", NumVoyageController::class],
    ["/consignation/te", TEController::class],
    ["/consignation/stats/[*:ids]?", StatsConsignationController::class],
    ["/consignation/navires", ListeNaviresConsignationController::class],
    ["/consignation/marchandises", ListeMarchandisesConsignationController::class],
    ["/consignation/clients", ListeClientsConsignationController::class],
    ["/consignation/navires-en-activite", NaviresEnActiviteController::class],

    // Chartering
    ["/chartering/charters/[i:id]?", CharterController::class],

    // Utilitaires
    ["/ports/[a:locode]?", PortController::class],
    ["/pays/[a:iso]?", CountryController::class],
    ["/marees/[i:year]?", TideController::class],
    ["/marees/annees", TideController::class],

    // Config
    ["/config/agence/[a:service]?", AgenceController::class],
    ["/config/bandeau-info/[i:id]?", BandeauInfoController::class],
    ["/config/pdf/[i:id]?", ConfigPDFController::class],
    ["/config/pdf/visu", VisualiserPDFController::class],
    ["/config/pdf/envoi", EnvoiPDFController::class],
    ["/config/ajouts-rapides/[i:id]?", AjoutRapideController::class],
    ["/config/cotes/[a:cote]?", CoteController::class],

    // Tiers
    ["/tiers/[i:id]?", ThirdPartyController::class],
    ["/tiers/[i:id]?/nombre_rdv", AppointmentCountController::class],

    // Admin
    ["/admin/users/[a:uid]?", UserAccountController::class],

    // Utilisateur
    ["/user", UserController::class],
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
        $controller = new RootController(true);
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
