<?php
require __DIR__ . "/../bootstrap.php";

use App\Core\Router;
use App\Core\HTTP\HTTPResponse;
use App\Core\Security;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
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

if (Security::check_if_request_can_be_done() === false) {
    (new HTTPResponse(429))
        ->addHeader("Retry-After", (string) Security::BLOCKED_IP_TIMEOUT)
        ->setType("text/plain")
        ->setBody("IP address blocked. Too many unauthenticated requests.")
        ->send();
}

/**
 * Routes de l'API.
 * @var array
 */
$routes = [
    // Affichage général
    ["/", fn () => new Root()],

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
    ["/consignation/stats/[*:ids]?", fn ($ids = null) => new StatsConsignation($ids)],
    ["/consignation/navires", fn () => new NaviresConsignation()],
    ["/consignation/marchandises", fn () => new MarchandisesConsignation()],
    ["/consignation/clients", fn () => new ClientsConsignation()],
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
        // 404 Not Found
        new Root(true);
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
} catch (Throwable $e) {
    error_logger($e);
    (new HTTPResponse(500))
        ->setType("text")
        ->setBody("Erreur serveur")
        ->send();
}
