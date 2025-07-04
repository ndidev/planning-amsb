<?php

// Path: api/public/index.php

declare(strict_types=1);

require_once __DIR__ . "/../bootstrap.php";

use App\Controller\Admin\UserAccountController;
use App\Controller\Bulk\AppointmentCountController as BulkProductAppointmentCountController;
use App\Controller\Bulk\BulkAppointmentController;
use App\Controller\Bulk\BulkDispatchController;
use App\Controller\Bulk\BulkProductController;
use App\Controller\Chartering\CharterController;
use App\Controller\Config\AgencyController;
use App\Controller\Config\ChartDatumController;
use App\Controller\Config\InfoBannerController;
use App\Controller\Config\PdfConfigController;
use App\Controller\Config\PdfViewerController;
use App\Controller\Config\QuickAppointmentAddController;
use App\Controller\Config\TimberQuickAppointmentAddController;
use App\Controller\Controller;
use App\Controller\ErrorController;
use App\Controller\RootController;
use App\Controller\Shipping\DraftsPerTonnageController;
use App\Controller\Shipping\ShipNamesController;
use App\Controller\Shipping\ShippingCallController;
use App\Controller\Shipping\ShippingCargoListController;
use App\Controller\Shipping\ShippingCustomersListController;
use App\Controller\Shipping\ShippingStatsController;
use App\Controller\Shipping\ShipsInOpsController;
use App\Controller\Shipping\VoyageNumberController;
use App\Controller\Stevedoring\CallsWithoutReportController;
use App\Controller\Stevedoring\DispatchController;
use App\Controller\Stevedoring\EquipmentController;
use App\Controller\Stevedoring\IgnoredShippingCallsContoller;
use App\Controller\Stevedoring\ShipReportController;
use App\Controller\Stevedoring\ShipReportPdfController;
use App\Controller\Stevedoring\ShipReportsFilterDataController;
use App\Controller\Stevedoring\StaffController;
use App\Controller\Stevedoring\SubcontractorsDataController;
use App\Controller\Stevedoring\TempWorkDispatchForDateController;
use App\Controller\Stevedoring\TempWorkHoursController;
use App\Controller\Stevedoring\TempWorkHoursReportController;
use App\Controller\ThirdParty\AppointmentCountController as ThirdPartyAppointmentCountController;
use App\Controller\ThirdParty\ThirdPartyController;
use App\Controller\ThirdParty\ThirdPartyContactController;
use App\Controller\Timber\TimberAppointmentController;
use App\Controller\Timber\TimberDeliveryNoteController;
use App\Controller\Timber\TimberRegistryController;
use App\Controller\Timber\TimberStatsController;
use App\Controller\Timber\TransportSuggestionsController;
use App\Controller\User\UserController;
use App\Controller\Utils\CountryController;
use App\Controller\Utils\PortController;
use App\Controller\Utils\TideController;
use App\Core\Array\Environment;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPResponse;
use App\Core\Router\Router;
use App\Core\Security;

set_exception_handler([ErrorController::class, "handleEmergency"]);

if (Security::checkIfRequestCanBeDone() === false) {
    new HTTPResponse(HTTPResponse::HTTP_TOO_MANY_REQUESTS_429)
        ->addHeader("Retry-After", (string) Security::BLOCKED_IP_TIMEOUT)
        ->setType("text/plain")
        ->setBody("IP address blocked. Too many unauthenticated requests.")
        ->send();
}

/**
 * Routes de l'API.
 * @var list<array{0: string, 1: class-string}> $routes
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
    ["/vrac/produits/[i:id]/nombre_rdv", BulkProductAppointmentCountController::class],
    ["/vrac/dispatch", BulkDispatchController::class],

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

    // Manutention
    ["/manutention/personnel/[i:id]?", StaffController::class],
    ["/manutention/equipements/[i:id]?", EquipmentController::class],
    ["/manutention/dispatch", DispatchController::class],
    ["/manutention/dispatch-interimaire/[date:date]", TempWorkDispatchForDateController::class],
    ["/manutention/heures-interimaires/[i:id]?", TempWorkHoursController::class],
    ["/manutention/heures-interimaires/rapport", TempWorkHoursReportController::class],
    ["/manutention/rapports-navires/[i:id]?", ShipReportController::class],
    ["/manutention/rapports-navires/[i:id]/pdf", ShipReportPdfController::class],
    ["/manutention/rapports-navires/filter-data", ShipReportsFilterDataController::class],
    ["/manutention/rapports-navires/calls-without-report", CallsWithoutReportController::class],
    ["/manutention/rapports-navires/ignored-shipping-calls/[i:id]?", IgnoredShippingCallsContoller::class],
    ["/manutention/rapports-navires/sous-traitants", SubcontractorsDataController::class],

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
    ["/tiers/[i:id]/nombre_rdv", ThirdPartyAppointmentCountController::class],
    ["/tiers/[i:id]/contacts", ThirdPartyContactController::class],

    // Admin
    ["/admin/users/[a:uid]?", UserAccountController::class],

    // Utilisateur
    ["/user", UserController::class],
];

$response = new HTTPResponse();

try {
    $router = new Router(
        $routes,
        Environment::getString('API_PATH'),
        ['date' => '\d{4}-\d{2}-\d{2}']
    );
    $match = $router->match();

    if (!\is_array($match)) {
        throw new NotFoundException("Route not found");
    }

    $controllerClass = $match["target"];
    $params = $match["params"];
    $name = $match["name"];

    if (!\is_string($controllerClass) || !\class_exists($controllerClass)) {
        throw new ServerException("Controller not found");
    }

    /** @var Controller $controller */
    $controller = new $controllerClass(...$params);

    $response = $controller->getResponse();

    $controller->sse->notify();
} catch (\Throwable $e) {
    $response = new ErrorController($e)->getResponse();
} finally {
    $response->send();
}
