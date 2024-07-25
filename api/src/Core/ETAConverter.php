<?php

namespace App\Core;

class ETAConverter
{

    /**
     * Heures correspondantes pour ETA "imprécises"
     */
    private const CORRESPONDANCE_ETA = [
        'EAM' => '02:00',
        'NUIT' => '03:00',
        'AM' => '06:00',
        'MATIN' => '06:00',
        'LAM' => '10:00',
        'NOON' => '12:00',
        'EPM' => '13:00',
        'PM' => '16:00',
        'APREM' => '16:00',
        'APRÈM' => '16:00',
        'APRÈS-MIDI' => '16:00',
        'APRES-MIDI' => '16:00',
        'APRES MIDI' => '16:00',
        'APRÈS MIDI' => '16:00',
        'SOIR' => '20:00',
        'EVENING' => '20:00',
        'EVE' => '20:00',
        'LPM' => '22:00',
        'MINUIT' => '24:00'
    ];

    /**
     * Conversion d'heure d'ETA en chiffres.
     * 
     * Fonction permettant de convertir des heures "imprécises"
     * (du type 'EAM', 'LPM', etc...) en chiffres (de type '16:00~')
     * pour affichage ordonné dans le planning
     * (MySQL affiche les RDV en fonction de la colonne 'eta_heure').
     * 
     * Utilisé dans le planning consignation.
     * 
     * @param string $heure          Heure à modifier si nécessaire.
     * @param array  $correspondance Tableau des correspondances entre chiffres et lettres.
     * 
     * @return string Heure convertie en chiffres.
     */
    public static function toDigits(string $heure, array $correspondance = static::CORRESPONDANCE_ETA): string
    {
        foreach ($correspondance as $lettres => $chiffres) {
            $heure = preg_replace("/\b$lettres\b/i", "$chiffres~", $heure);
        }
        return $heure;
    }


    /**
     * Conversion d'heure d'ETA en lettres.
     * 
     * Fonction permettant de convertir des heures SQL "imprécises"
     * (du type '16:00~') en lettres (de type 'PM') pour affichage dans le planning.
     * 
     * Utilisé dans le planning consignation.
     * 
     * @param string $heure          Heure à modifier si nécessaire.
     * @param array  $correspondance Tableau des correspondances entre chiffres et lettres.
     * 
     * @return string Heure convertie en lettres.
     */
    public static function toLetters(string $heure, $correspondance = static::CORRESPONDANCE_ETA): string
    {
        foreach ($correspondance as $lettres => $chiffres) {
            $heure = preg_replace("/\b$chiffres~/i", $lettres, $heure);
        }
        return $heure;
    }
}
