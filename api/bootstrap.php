<?php

// Path: api/bootstrap.php

declare(strict_types=1);

use App\Core\Array\Environment;
use App\Core\Array\Server;

setlocale(LC_ALL, "fr_FR.utf8", "fr-FR"); // Seul "fr_FR.utf8" fonctionne sur le serveur Axiom, seul "fr-FR" fonctionne en local
date_default_timezone_set('Europe/Paris');

define("ROOTPATH", __DIR__ . "/..");
define('API', ROOTPATH . '/api');
define('FONTS', API . '/font');
define('UNIFONTS', FONTS . '/unifont');
define('LOGOS', ROOTPATH . '/logos');
define('LOG_DIR', API . '/var/log');

require_once API . "/vendor/autoload.php";
require_once API . "/src/Core/Component/Constants.php";

// Chargement des variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(ROOTPATH, ".env");
$dotenv->load();

// Inscrit l'URL de l'API dans l'environnement
// (URL changeante en fonction de l'accès local/extranet et du protocole HTTP/HTTPS)
$scheme = Server::getString('REQUEST_SCHEME', 'http');
$host = Server::getString('HTTP_HOST');

$api_path = Environment::getString('API_PATH');
$api_url = $scheme . '://' . $host . $api_path;
Environment::put('API_URL', $api_url);

$logos_path = Environment::getString('LOGOS_PATH');
$logos_url = $scheme . '://' . $host . $logos_path;
Environment::put('LOGOS_URL', $logos_url);

if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0777, true);
}
