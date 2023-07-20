<?php

namespace Api\Utils;

use \DateTime;
use \DateInterval;
use \DateTimeZone;
use \DateTimeInterface;
use \IntlDateFormatter;

class DateUtils
{
  public const TIMEZONE = "Europe/Paris";

  /** Exemple : `2023-07-24` */
  public const ISO_DATE = "yyyy-MM-dd";

  /** Exemple : `13:56` */
  public const ISO_TIME = "HH:mm";

  /** Exemple : `2023-07-24T11:56:47Z` */
  public const ISO_DATETIME_FULL = "yyyy-MM-ddTHH:mm:ssZ";

  /** Exemple : `lundi 24 juillet 2023` */
  public const DATE_FULL = "EEEE dd MMMM yyyy";

  /** Exemple : `lundi 24 juillet 2023 13:56:47` */
  public const DATETIME_FULL = "EEEE dd MMMM yyyy HH:mm:ss";

  /** Exemple : `2023-07-24` */
  public const SQL_DATE = "yyyy-MM-dd";

  /** Exemple : `13:56:47` */
  public const SQL_TIME = "HH:mm:ss";

  /** Exemple : `2023-07-24 13:56:47` */
  public const SQL_TIMESTAMP = "yyyy-MM-dd HH:mm:ss";

  /**
   * Formate une date.
   * 
   * @param string                   $pattern     Format de la date (formats possibles : https://unicode-org.github.io/icu/userguide/format_parse/datetime/).
   * @param string|DateTimeInterface $date        Date sous forme de chaîne de caractères ou `\DateTimeInterface`.
   * @param string                   $locale      Optionnel. Langue de formattage de la date.
   * 
   * @return string Date formatée.
   */
  public static function format(
    string $pattern,
    DateTimeInterface|string $date,
    ?string $locale = "fr_FR"
  ): string {
    $timezone = new DateTimeZone(self::TIMEZONE);

    $formatter = new IntlDateFormatter(
      $locale,
      IntlDateFormatter::FULL,
      IntlDateFormatter::FULL,
      $timezone,
      IntlDateFormatter::GREGORIAN,
      $pattern
    );

    if (!($date instanceof DateTimeInterface)) {
      $datetime = new DateTime($date, $timezone);
    } else {
      $datetime = $date;
    }

    return $formatter->format($datetime);
  }

  /**
   * Convertit une chaîne de caractère en objet `DateTime`.
   * 
   * Si la date est déjà au format `DateTime`, retourne l'objet intact.
   * 
   * @param DateTime|string $date 
   * 
   * @return DateTime 
   */
  public static function convertirDate(DateTime|string $date): DateTime
  {
    if ($date instanceof DateTime) {
      $datetime = $date;
    } else {
      if (str_contains($date, "/")) {
        $date_array = explode("/", $date);
        $date_ymd = join("-", array_reverse($date_array));
      } else {
        $date_ymd = $date;
      }

      $datetime = new DateTime($date_ymd);
    }

    return $datetime;
  }

  /**
   * Vérifie si une date est un jour férié.
   * 
   * @param DateTime|string $date Date à vérifier.
   * 
   * @return bool TRUE si jour férié, FALSE sinon
   */
  public static function verifierJourFerie(DateTime|string $date): bool
  {
    $date = self::convertirDate($date);

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


    // Vérification du jour férié
    foreach ($feries as $jour_ferie) {
      if ($date->setTime(0, 0) == $jour_ferie) {
        return TRUE;
      }
    }

    return FALSE;
  }



  /**
   * Vérifie si la date passée en paramètre est un jour ouvré.
   * 
   * @param DateTime|string $date Date à vérifier.
   * 
   * @return bool TRUE si jour ouvré, FALSE si jour chômé.
   */
  public static function verifierJourOuvre(DateTime|string $date): bool
  {
    $date = self::convertirDate($date);

    if (self::verifierJourFerie($date)) {
      return FALSE;
    }

    /**
     * @var int Jour de la semaine : de 1 (lundi) à 7 (dimanche)
     */
    $jour = (int) $date->format("N"); // date('N') renvoie le chiffre du jour (1 = lundi ... 7 = dimanche)

    switch ($jour) {
      case 6: // date = samedi
      case 7: // date = dimanche
        return FALSE;
      default:
        return TRUE;
    }
  }

  /**
   * Retourne le $nombreJours_ième jour ouvré avant la date entrée en paramètre.
   * 
   * La fonction décale d'un jour en arrière et vérifie à chaque fois si le nouveau jour est ouvré ou non.  
   * Si oui, retourne la nouvelle date.  
   * Si non, nouvelle itération.
   * 
   * Exemple : retourne le jeudi si la date entrée est un samedi avec $nombreJours = '2' (en ne supposant aucun jour férié)
   * 
   * @param DateTime|string $date        Date.
   * @param int             $nombreJours Optionnel. Nombre de jours avant $date. Défaut = 1.
   * 
   * @return DateTime
   */
  public static function jourOuvrePrecedent(
    DateTime|string $date,
    ?int $nombreJours = 1
  ): DateTime {
    $date = self::convertirDate($date);

    $jour_ouvre_precedent = clone $date;

    for ($i = 0; $i < $nombreJours; $i++) {
      do {
        $jour_ouvre_precedent->sub(new DateInterval("P1D"));
      } while (!self::verifierJourOuvre($jour_ouvre_precedent));
    }

    return $jour_ouvre_precedent;
  }

  /**
   * Retourne le $nombreJours_ième jour ouvré après la date entrée en paramètre.
   * 
   * La fonction décale d'un jour en arrière et vérifie à chaque fois si le nouveau jour est ouvré ou non.
   * Si oui, retourne la nouvelle date.
   * Si non, nouvelle itération.
   * 
   * Exemple : retourne le mardi si la date entrée est un samedi avec $nombreJours = '2' (en ne supposant aucun jour férié)
   * 
   * @param DateTime|string $date         Date.
   * @param int             $nombreJours Optionnel. Nombre de jours après $date. Défaut = 1.
   * 
   * @return DateTime
   */
  public static function jourOuvreSuivant(
    DateTime|string $date,
    ?int $nombreJours = 1
  ): DateTime {
    $date = self::convertirDate($date);

    $jour_ouvre_suivant = clone $date;

    for ($i = 0; $i < $nombreJours; $i++) {
      do {
        $jour_ouvre_suivant->add(new DateInterval("P1D"));
      } while (!self::verifierJourOuvre($jour_ouvre_suivant));
    }

    return $jour_ouvre_suivant;
  }
}
