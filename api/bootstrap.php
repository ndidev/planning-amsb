<?php
setlocale(LC_ALL, "fr_FR.utf8", "fr-FR"); // Seul "fr_FR.utf8" fonctionne sur le serveur Axiom, seul "fr-FR" fonctionne en local
date_default_timezone_set('Europe/Paris');

define("ROOTPATH", __DIR__ . "/..");
define('API', ROOTPATH . '/api');
define('FONTS', API . '/font');
define('UNIFONTS', FONTS . '/unifont');
define('LOGOS', ROOTPATH . '/logos');

require_once API . "/vendor/autoload.php";
require_once API . "/src/Core/Component/Constants.php";

// Chargement des variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(ROOTPATH, ".env");
$dotenv->load();

// Inscrit l'URL de l'API dans l'environnement
// (URL changeante en fonction de l'accès local/extranet et du protocole HTTP/HTTPS)
if (array_key_exists("HTTP_HOST", $_SERVER)) {
    $scheme = $_SERVER["REQUEST_SCHEME"] ?? "http";
    $host = $_SERVER["HTTP_HOST"];

    $api_path = $_ENV["API_PATH"];
    $api_url = $scheme . "://" . $host . $api_path;
    $_ENV["API_URL"] = $api_url;

    $logos_path = $_ENV["LOGOS_PATH"];
    $logos_url = $scheme . "://" . $host . $logos_path;
    $_ENV["LOGOS_URL"] = $logos_url;
}
