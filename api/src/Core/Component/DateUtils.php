<?php

// Path: api/src/Core/Component/DateUtils.php

declare(strict_types=1);

namespace App\Core\Component;

/**
 * Date utilities.
 * 
 * @package App\Core
 */
abstract class DateUtils
{
    public const TIMEZONE = "Europe/Paris";

    /** Example : `2023-07-24` */
    public const ISO_DATE = "yyyy-MM-dd";

    /** Example : `13:56` */
    public const ISO_TIME = "HH:mm";

    /** Example : `2023-07-24T11:56:47Z` */
    public const ISO_DATETIME_FULL = "yyyy-MM-ddTHH:mm:ssZ";

    /** Example : `lundi 24 juillet 2023` */
    public const DATE_FULL = "EEEE dd MMMM yyyy";

    /** Example : `lundi 24 juillet 2023 13:56:47` */
    public const DATETIME_FULL = "EEEE dd MMMM yyyy HH:mm:ss";

    /** Example : `2023-07-24` */
    public const SQL_DATE = "yyyy-MM-dd";

    /** Example : `13:56:47` */
    public const SQL_TIME = "HH:mm:ss";

    /** Example : `2023-07-24 13:56:47` */
    public const SQL_TIMESTAMP = "yyyy-MM-dd HH:mm:ss";

    /**
     * Format a date.
     * 
     * @param string                    $pattern     Date format (valid formats: https://unicode-org.github.io/icu/userguide/format_parse/datetime/).
     * @param string|\DateTimeInterface $date        Date as a string or `\DateTimeInterface`.
     * @param ?string                   $locale      Optional. Locale.
     * 
     * @return string Formated date.
     */
    public static function format(
        string $pattern,
        string|\DateTimeInterface $date,
        ?string $locale = "fr_FR"
    ): string {
        $timezone = new \DateTimeZone(self::TIMEZONE);

        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            $timezone,
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );

        if (!($date instanceof \DateTimeInterface)) {
            $datetime = new \DateTime($date, $timezone);
        } else {
            $datetime = $date;
        }

        $formattedDate = $formatter->format($datetime);

        if ($formattedDate === false) {
            throw new \Exception("Date format error.");
        }

        return $formattedDate;
    }

    /**
     * Convert a string to a `DateTime` object.
     * 
     * If the date is already a `DateTimeInterface` object, return the date untouched.
     * 
     * @param \DateTimeInterface|string $date 
     * 
     * @return \DateTime 
     */
    public static function convertDate(\DateTimeInterface|string $date): \DateTime
    {
        if ($date instanceof \DateTimeInterface) {
            $datetime = \DateTime::createFromInterface($date);
        } else {
            if (str_contains($date, "/")) {
                $dateArray = explode("/", $date);
                $dateYmd = join("-", array_reverse($dateArray));
            } else {
                $dateYmd = $date;
            }

            $datetime = new \DateTime($dateYmd);
        }

        return $datetime;
    }

    /**
     * Check if a date is a public holiday.
     * 
     * Note: only checks for public holiday in France.
     * 
     * @param \DateTimeInterface|string $date Date to check.
     * 
     * @return bool `true` if public holiday, `false` otherwise.
     */
    public static function isPublicHoliday(\DateTimeInterface|string $date): bool
    {
        $date = self::convertDate($date);

        $year = (int) $date->format("Y");

        $easter = new \DateTime("@" . easter_date($year));

        /**
         * @var \DateTime[] Public holidays list.
         */
        $public_holidays = [
            "jour_an" => new \DateTime("$year-01-01"),
            "paques" => $easter,
            "lundi_paques" => (clone $easter)->add(new \DateInterval("P1D")),
            "fete_travail" => new \DateTime("$year-05-01"),
            "victoire_1945" => new \DateTime("$year-05-08"),
            "ascension" => (clone $easter)->add(new \DateInterval("P39D")),
            "pentecote" => (clone $easter)->add(new \DateInterval("P49D")),
            "lundi_pentecote" => (clone $easter)->add(new \DateInterval("P50D")),
            "fete_nationale" => new \DateTime("$year-07-14"),
            "assomption" => new \DateTime("$year-08-15"),
            "toussaint" => new \DateTime("$year-11-01"),
            "armistice_1918" => new \DateTime("$year-11-11"),
            "noel" => new \DateTime("$year-12-25"),
        ];


        // Check public holiday
        foreach ($public_holidays as $holiday) {
            if ($date->setTime(0, 0) == $holiday) {
                return true;
            }
        }

        return false;
    }



    /**
     * Check if a date is a working day.
     * 
     * Note: only checks for working days in France.
     * 
     * @param \DateTimeInterface|string $date Date to check.
     * 
     * @return bool `true` if working day, `false` otherwise.
     */
    public static function isWorkingDay(\DateTimeInterface|string $date): bool
    {
        $date = self::convertDate($date);

        if (self::isPublicHoliday($date)) {
            return false;
        }

        /**
         * @var int Week day : from 1 (Monday) to 7 (Sunday)
         */
        $day = (int) $date->format("N"); // date('N') returns the number of the day (1 = Monday ... 7 = Sunday)

        switch ($day) {
            case 6: // date = Saturday
            case 7: // date = Sunday
                return false;
            default:
                return true;
        }
    }

    /**
     * Return the `$offset`ᵗʰ working day before `$date`.
     * 
     * The function offsets the date by one day in the past and checks if the new date is a working day.  
     * If yes, returns the new date.  
     * If no, new iteration.
     * 
     * Example: returs the previous Thursday if the date is a Saturday and `$offset` = 2 (assuming no public holiday in between).
     * 
     * @param \DateTimeInterface|string $date   Date.
     * @param int                       $offset Optional. Number of days before $date. Default = 1.
     * 
     * @return \DateTime
     */
    public static function getPreviousWorkingDay(
        \DateTimeInterface|string $date,
        ?int $offset = 1
    ): \DateTime {
        $date = self::convertDate($date);

        $previous_working_day = clone $date;

        for ($i = 0; $i < $offset; $i++) {
            do {
                $previous_working_day->sub(new \DateInterval("P1D"));
            } while (!self::isWorkingDay($previous_working_day));
        }

        return $previous_working_day;
    }

    /**
     * Retourne le $nombreJours_ième jour ouvré après la date entrée en paramètre.
     * 
     * The function offsets the date by one day in the future and checks if the new date is a working day.  
     * If yes, returns the new date.  
     * If no, new iteration.
     * 
     * Example: returs the next Tuesday if the date is a Saturday and `$offset` = 2 (assuming no public holiday in between).
     * 
     * @param \DateTimeInterface|string $date   Date.
     * @param int                       $offset Optional. Number of days after $date. Default = 1.
     * 
     * @return \DateTime
     */
    public static function getNextWorkingDay(
        \DateTimeInterface|string $date,
        ?int $offset = 1
    ): \DateTime {
        $date = self::convertDate($date);

        $next_working_day = clone $date;

        for ($i = 0; $i < $offset; $i++) {
            do {
                $next_working_day->add(new \DateInterval("P1D"));
            } while (!self::isWorkingDay($next_working_day));
        }

        return $next_working_day;
    }

    /**
     * Make a DateTimeImmutable object from an input date or time.
     * 
     * @param \DateTimeInterface|string|null $datetime 
     */
    public static function makeDateTimeImmutable(\DateTimeInterface|string|null $datetime): ?\DateTimeImmutable
    {
        try {
            if (is_null($datetime)) {
                return null;
            }

            if (\is_string($datetime)) {
                return new \DateTimeImmutable($datetime);
            }

            if ($datetime instanceof \DateTimeInterface) {
                return \DateTimeImmutable::createFromInterface($datetime);
            }
        } catch (\Throwable) {
            return null;
        }
    }
}
