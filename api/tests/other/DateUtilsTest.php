<?php

declare(strict_types=1);

setlocale(LC_ALL, "fr_FR.utf8", "fr-FR"); // Seul "fr_FR.utf8" fonctionne sur le serveur Axiom, seul "fr-FR" fonctionne en local
date_default_timezone_set('Europe/Paris');

require_once __DIR__ . "/../vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use App\Core\DateUtils;

final class DateUtilsTest extends TestCase
{
    public function testOutputsValidDateString(): void
    {
        $date = "2022-02-28T20:01:36+0100";
        $expected = "lundi 28 février 2022";
        $actual = DateUtils::format(DateUtils::DATE_FULL, $date);

        $this->assertEquals($expected, $actual);
    }

    public function testVerifierJourDeLAnFerie(): void
    {
        $date = "2022-01-01";
        $this->assertTrue(DateUtils::checkPublicHoliday(new DateTime($date)));
    }

    public function testVerifierLundiPaquesFerie(): void
    {
        $paques = "2022-04-18";
        $this->assertTrue(DateUtils::checkPublicHoliday(new DateTime($paques)));
    }

    public function testVerifierJeudiAscensionFerie(): void
    {
        $ascension = "2022-05-26";
        $this->assertTrue(DateUtils::checkPublicHoliday(new DateTime($ascension)));
    }

    public function testVerifierLunidiPentecoteFerie(): void
    {
        $pentecote = "2022-06-06";
        $this->assertTrue(DateUtils::checkPublicHoliday(new DateTime($pentecote)));
    }

    public function testVerifierJourOuvre(): void
    {
        $date = "2022-01-03";
        $this->assertTrue(DateUtils::checkWorkingDay(new DateTime($date)));
    }

    public function testJourOuvreSuivant(): void
    {
        $date = "2022-01-01";
        $expected = "2022-01-03";
        $actual = DateUtils::nextWorkingDay(new DateTime($date));
        $this->assertEquals($expected, DateUtils::format(DateUtils::ISO_DATE, $actual));
    }

    public function testJourOuvrePrecedent(): void
    {
        $date = "2022-06-07";
        $expected = "2022-06-03";
        $actual = DateUtils::previousWorkingDay(new DateTime($date));
        $this->assertEquals($expected, DateUtils::format(DateUtils::ISO_DATE, $actual));
    }

    // public function testAujourdhuiJour(): void
    // {
    //   $aujourdhui = (new DateTime())->setTime(0, 0);
    //   $actual = DateUtils::format(DateUtils::SQL_TIMESTAMP, $aujourdhui);
    //   $expected = "2022-06-07 00:00:00";
    //   $this->assertEquals($expected, $actual);
    // }

    public function testVerifierJourFerieAvecHeure(): void
    {
        $ascension = "2022-05-26 15:41:23";
        $this->assertTrue(DateUtils::checkPublicHoliday(new DateTime($ascension)));
    }
}
