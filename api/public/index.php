<?php
require __DIR__ . "/../bootstrap.php";

use App\Core\Router;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
use App\Controller\RootController;
use App\Controller\Timber\TimberAppointmentController as RdvBois;
use App\Controller\Timber\TimberRegisterController as RegistreBois;
use App\Controller\Timber\TimberStatsController as StatsBois;
use App\Controller\Timber\TransportSuggestionsController;
use App\Controller\Bulk\BulkAppointmentController;
use App\Controller\Bulk\BulkProductController;
use App\Controller\Consignation\EscaleController as EscaleConsignation;
use App\Controller\Consignation\NumVoyageController as NumVoyageConsignation;
use App\Controller\Consignation\TEController as TE;
use App\Controller\Consignation\StatsController as StatsConsignation;
use App\Controller\Consignation\NaviresEnActiviteController;
use App\Controller\Consignation\ListeNaviresController as NaviresConsignation;
use App\Controller\Consignation\ListeMarchandisesController as MarchandisesConsignation;
use App\Controller\Consignation\ListeClientsController as ClientsConsignationController;
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
use App\Controller\Config\PDF\EnvoiPDFController;
use App\Controller\Config\AjoutRapideController;
use App\Controller\Config\CoteController;
use App\Controller\User\UserController as UserManagementController;
use App\Controller\Admin\UserAccountController;

if (Security::checkIfRequestCanBeDone() === false) {
    (new HTTPResponse(HTTPResponse::HTTP_TOO_MANY_REQUESTS_429))
        ->addHeader("Retry-After", (string) Security::BLOCKED_IP_TIMEOUT)
        ->setType("text/plain")
        ->setBody("IP address blocked. Too many unauthenticated requests.")
        ->send();
}

/**
 * API routes.
 */
$routes = [
    // Affichage général
    ["/", fn () => new RootController()],

    // Bois
    ["/bois/rdvs/[i:id]?", fn ($id = null) => new RdvBois($id)],
    ["/bois/registre", fn () => new RegistreBois()],
    ["/bois/stats", fn () => new StatsBois()],
    ["/bois/suggestions-transporteurs", fn () => new TransportSuggestionsController()],

    // Vrac
    ["/vrac/rdvs/[i:id]?", fn ($id = null) => new BulkAppointmentController($id)],
    ["/vrac/produits/[i:id]?", fn ($id = null) => new BulkProductController($id)],

    // Consignation
    ["/consignation/escales/[i:id]?", fn ($id = null) => new EscaleConsignation($id)],
    ["/consignation/voyage", fn () => new NumVoyageConsignation()],
    ["/consignation/te", fn () => new TE()],
    ["/consignation/stats/[*:ids]?", fn ($ids = null) => new StatsConsignation($ids)],
    ["/consignation/navires", fn () => new NaviresConsignation()],
    ["/consignation/marchandises", fn () => new MarchandisesConsignation()],
    ["/consignation/clients", fn () => new ClientsConsignationController()],
    ["/consignation/navires-en-activite", fn () => new NaviresEnActiviteController()],

    // Chartering
    ["/chartering/charters/[i:id]?", fn ($id = null) => new CharterController($id)],

    // Utilitaires
    ["/ports/[a:locode]?", fn ($locode = null) => new PortController($locode)],
    ["/pays/[a:iso]?", fn ($iso = null) => new CountryController($iso)],
    ["/marees/[i:annee]?", fn ($annee = null) => new TideController($annee)],
    ["/marees/annees", fn () => new TideController(annees: true)],

    // Config
    ["/config/agence/[a:service]?", fn ($service = null) => new AgenceController($service)],
    ["/config/bandeau-info/[i:id]?", fn ($id = null) => new BandeauInfoController($id)],
    ["/config/pdf/[i:id]?", fn ($id = null) => new ConfigPDFController($id)],
    ["/config/pdf/visu", fn () => new VisualiserPDFController()],
    ["/config/pdf/envoi", fn () => new EnvoiPDFController()],
    ["/config/ajouts-rapides/[i:id]?", fn ($id = null) => new AjoutRapideController($id)],
    ["/config/cotes/[a:cote]?", fn ($cote = null) => new CoteController($cote)],

    // Tiers
    ["/tiers/[i:id]?", fn ($id = null) => new ThirdPartyController($id)],
    ["/tiers/[i:id]?/nombre_rdv", fn ($id = null) => new AppointmentCountController($id)],

    // Admin
    ["/admin/users/[a:uid]?", fn ($uid = null) => new UserAccountController($uid)],

    // Utilisateur
    ["/user", fn () => new UserManagementController()],
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
        // 404 Not Found
        new RootController(true);
    }
} catch (ClientException $e) {
    (new HTTPResponse($e->http_status))
        ->setType("text")
        ->setBody($e->getMessage())
        ->send();
} catch (ServerException $e) {
    error_logger($e);
    (new HTTPResponse($e->http_status))
        ->setType("text")
        ->setBody("Erreur serveur")
        ->send();
} catch (\Throwable $e) {
    error_logger($e);
    (new HTTPResponse(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR_500))
        ->setType("text")
        ->setBody("Erreur serveur")
        ->send();
}
