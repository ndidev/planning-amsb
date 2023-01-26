<?php
setlocale(LC_ALL, "fr_FR.utf8", "fr-FR"); // Seul "fr_FR.utf8" fonctionne sur le serveur Axiom, seul "fr-FR" fonctionne en local
date_default_timezone_set('Europe/Paris');

require_once __DIR__ . "/../vendor/autoload.php";

use Api\Utils\DateUtils;

$date = new DateTime();

$annee = (int) $date->format("Y");

$paques = new DateTime("@" . easter_date($annee));

/**
 * @var DateTime[] Liste des jours fériés.
 */
$feries = [
  "jour_an" => new DateTime("$annee-01-01"),
  "paques" => $paques,
  "lundi_paques" => (clone $paques)->add(new DateInterval("P1D")),
  "fete_travail" => new DateTime("$annee-05-01"),
  "victoire_1945" => new DateTime("$annee-05-08"),
  "ascension" => (clone $paques)->add(new DateInterval("P39D")),
  "pentecote" => (clone $paques)->add(new DateInterval("P49D")),
  "lundi_pentecote" => (clone $paques)->add(new DateInterval("P50D")),
  "fete_nationale" => new DateTime("$annee-07-14"),
  "assomption" => new DateTime("$annee-08-15"),
  "toussaint" => new DateTime("$annee-11-01"),
  "armistice_1918" => new DateTime("$annee-11-11"),
  "noel" => new DateTime("$annee-12-25"),
];

foreach ($feries as $jour => $date_ferie) {
  echo $jour . " => " . DateUtils::format(DateUtils::DATETIME_FULL, $date_ferie) . PHP_EOL;
}
