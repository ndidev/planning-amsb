<?php

/**
 * Convertit une couleur RGB en couleur hexadécimal ou vice versa
 * Utilisé dans la génération PDF vrac (pour les marchandises)
 */


/**
 * Conversion RGB vers hexadécimal.
 * 
 * @param string $rgb Couleur au format "r, g, b".
 *
 * @return string Couleur au format #RRGGBB.
 */
function rgb_vers_hex(string $rgb = ''): string
{
  // Suppression des espaces dans la chaîne de caractères
  $rgb = str_replace(" ", "", $rgb);

  // Enregistrement des trois couleurs dans un tableau
  $couleurs = explode(',', $rgb);

  // Vérification qu'il y a bien 3 couleurs
  if (count($couleurs) !== 3) {
    return '#000000';
  }

  // Vérification que les couleurs sont valides
  foreach ($couleurs as $couleur) {
    if (!is_numeric($couleur) || $couleur < 0 || $couleur > 255) {
      return '#000000';
    }
  }

  return sprintf("#%02X%02X%02X", $couleurs[0], $couleurs[1], $couleurs[2]);
}

/**
 * Conversion hexadécimal vers RGB.
 * 
 * @param string $hex Couleur en hexadécimal.
 * 
 * @return string Couleur au format "r,g,b".
 */
function hex_vers_rgb(string $hex = ''): string
{

  // Si la chaîne comporte '#', on ne conserve que les chiffres
  if (strpos($hex, '#') === 0) {
    $hex = substr($hex, 1, strlen($hex));
  }

  $r = $g = $b = 0;

  // Vérification que la chaîne est au bon format (3 ou 6 caractères)
  // et qu'il s'agit bien d'un nombre hexadécimal correct
  if ((strlen($hex) === 3 || strlen($hex) === 6) && ctype_xdigit($hex)) {
    $longueur_couleur = strlen($hex) / 3;
    // Rouge
    $r = substr($hex, $longueur_couleur * 0, $longueur_couleur);
    $r = strlen($r) === 1 ? $r . $r : $r;
    $r = hexdec($r);
    // Vert
    $g = substr($hex, $longueur_couleur * 1, $longueur_couleur);
    $g = strlen($g) === 1 ? $g . $g : $g;
    $g = hexdec($g);
    // Bleu
    $b = substr($hex, $longueur_couleur * 2, $longueur_couleur);
    $b = strlen($b) === 1 ? $b . $b : $b;
    $b = hexdec($b);
  }

  return $r . ',' . $g . ',' . $b;
}
