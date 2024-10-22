<?php
require_once __DIR__ . "/../bootstrap.php";

use App\Controller\Controller;
use App\Controller\RootController;
use App\Controller\Admin\UserAccountController;
use App\Controller\Bulk\BulkAppointmentController;
use App\Controller\Bulk\BulkProductController;
use App\Controller\Chartering\CharterController;
use App\Controller\Config\AgencyController;
use App\Controller\Config\QuickAppointmentAddController;
use App\Controller\Config\TimberQuickAppointmentAddController;
use App\Controller\Config\ChartDatumController;
use App\Controller\Config\InfoBannerController;
use App\Controller\Config\PdfConfigController;
use App\Controller\Config\PdfViewerController;
use App\Controller\ErrorController;
use App\Controller\Shipping\ShipNamesController;
use App\Controller\Shipping\ShipsInOpsController;
use App\Controller\Shipping\ShippingCargoListController;
use App\Controller\Shipping\ShippingCustomersListController;
use App\Controller\Shipping\ShippingStatsController;
use App\Controller\Shipping\DraftsPerTonnageController;
use App\Controller\Shipping\ShippingCallController;
use App\Controller\Shipping\VoyageNumberController;
use App\Controller\ThirdParty\AppointmentCountController;
use App\Controller\ThirdParty\ThirdPartyController;
use App\Controller\Timber\TimberAppointmentController;
use App\Controller\Timber\TimberRegistryController;
use App\Controller\Timber\TimberStatsController;
use App\Controller\Timber\TransportSuggestionsController;
use App\Controller\Timber\TimberDeliveryNoteController;
use App\Controller\User\UserController;
use App\Controller\Utils\CountryController;
use App\Controller\Utils\PortController;
use App\Controller\Utils\TideController;
use App\Core\Router;
use App\Core\Security;
use App\Core\HTTP\HTTPResponse;

set_exception_handler([ErrorController::class, "handleEmergency"]);

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
    ["/bois/check-delivery-note-available", TimberDeliveryNoteController::class],

    // Vrac
    ["/vrac/rdvs/[i:id]?", BulkAppointmentController::class],
    ["/vrac/produits/[i:id]?", BulkProductController::class],

    // Consignation
    ["/consignation/escales/[i:id]?", ShippingCallController::class],
    ["/consignation/voyage", VoyageNumberController::class],
    ["/consignation/te", DraftsPerTonnageController::class],
    ["/consignation/stats/[*:ids]?", ShippingStatsController::class],
    ["/consignation/navires", ShipNamesController::class],
    ["/consignation/marchandises", ShippingCargoListController::class],
    ["/consignation/clients", ShippingCustomersListController::class],
    ["/consignation/navires-en-activite", ShipsInOpsController::class],

    // Chartering
    ["/chartering/charters/[i:id]?", CharterController::class],

    // Utilitaires
    ["/ports/[a:locode]?", PortController::class],
    ["/pays/[a:iso]?", CountryController::class],
    ["/marees/[i:year]?", TideController::class],
    ["/marees/annees", TideController::class],

    // Config
    ["/config/agence/[a:service]?", AgencyController::class],
    ["/config/bandeau-info/[i:id]?", InfoBannerController::class],
    ["/config/pdf/[i:id]?", PdfConfigController::class],
    ["/config/pdf/generer", PdfViewerController::class],
    ["/config/ajouts-rapides", QuickAppointmentAddController::class],
    ["/config/ajouts-rapides/bois/[i:id]?", TimberQuickAppointmentAddController::class],
    ["/config/cotes/[a:cote]?", ChartDatumController::class],

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
$response = new HTTPResponse();

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
} catch (\Throwable $e) {
    $response = (new ErrorController($e))->getResponse();
}

$response->send();
