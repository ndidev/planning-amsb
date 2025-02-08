<?php

// Path: api/src/Core/Component/ColorConverter.php

declare(strict_types=1);

namespace App\Core\Component;

/**
 * Convertit une couleur RGB en couleur hexadécimal ou vice versa
 * Utilisé dans la génération PDF vrac (pour les marchandises)
 */
final class ColorConverter
{
    /**
     * Conversion RGB vers hexadécimal.
     * 
     * @param string $rgb Couleur au format "r, g, b".
     *
     * @return string Couleur au format #RRGGBB.
     */
    public static function rgbToHex(string $rgb = ''): string
    {
        // Suppression des espaces dans la chaîne de caractères
        $rgb = \str_replace(" ", "", $rgb);

        // Enregistrement des trois couleurs dans un tableau
        $colors = explode(',', $rgb);

        // Vérification qu'il y a bien 3 couleurs
        if (count($colors) !== 3) {
            return '#000000';
        }

        // Vérification que les couleurs sont valides
        foreach ($colors as $color) {
            if (!\is_numeric($color) || $color < 0 || $color > 255) {
                return '#000000';
            }
        }

        return sprintf("#%02X%02X%02X", $colors[0], $colors[1], $colors[2]);
    }

    /**
     * Conversion hexadécimal vers RGB.
     * 
     * @param string $hex Couleur en hexadécimal.
     * 
     * @return string Couleur au format "r,g,b".
     */
    public static function hexToRgb(string $hex = ''): string
    {

        // Si la chaîne comporte '#', on ne conserve que les chiffres
        if (\strpos($hex, '#') === 0) {
            $hex = \substr($hex, 1, \strlen($hex));
        }

        $r = $g = $b = 0;

        // Vérification que la chaîne est au bon format (3 ou 6 caractères)
        // et qu'il s'agit bien d'un nombre hexadécimal correct
        if ((\strlen($hex) === 3 || \strlen($hex) === 6) && ctype_xdigit($hex)) {
            $colorStringLength = \strlen($hex) / 3;
            // Red
            $r = \substr($hex, $colorStringLength * 0, $colorStringLength);
            $r = \strlen($r) === 1 ? $r . $r : $r;
            $r = hexdec($r);
            // Green
            $g = \substr($hex, $colorStringLength * 1, $colorStringLength);
            $g = \strlen($g) === 1 ? $g . $g : $g;
            $g = hexdec($g);
            // Blue
            $b = \substr($hex, $colorStringLength * 2, $colorStringLength);
            $b = \strlen($b) === 1 ? $b . $b : $b;
            $b = hexdec($b);
        }

        return $r . ',' . $g . ',' . $b;
    }
}
