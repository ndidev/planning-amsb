<?php

// Path: api/tests/bootstrap.php

declare(strict_types=1);

setlocale(LC_ALL, "fr_FR.utf8", "fr-FR"); // Seul "fr_FR.utf8" fonctionne sur le serveur Axiom, seul "fr-FR" fonctionne en local
date_default_timezone_set('Europe/Paris');

define("ROOTPATH", __DIR__ . '/../..');
define('API', ROOTPATH . '/api');
define('FONTS', API . '/font');
define('UNIFONTS', FONTS . '/unifont');
define('LOGOS', ROOTPATH . '/logos');

require_once API . "/vendor/autoload.php";
require_once API . "/src/Core/Component/Constants.php";

// Chargement des variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(ROOTPATH, ".env.test");
$dotenv->load();
