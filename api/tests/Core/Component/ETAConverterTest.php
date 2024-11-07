<?php

// Path: api/tests/Core/Component/ETAConverterTest.php

namespace App\Tests\Core\Component;

use App\Core\Component\ETAConverter;
use PHPUnit\Framework\TestCase;

final class ETAConverterTest extends TestCase
{
    public function testToDigits(): void
    {
        $this->assertEquals('02:00~', ETAConverter::toDigits('EAM'));
        $this->assertEquals('03:00~', ETAConverter::toDigits('NUIT'));
        $this->assertEquals('06:00~', ETAConverter::toDigits('AM'));
        $this->assertEquals('06:00~', ETAConverter::toDigits('MATIN'));
        $this->assertEquals('10:00~', ETAConverter::toDigits('LAM'));
        $this->assertEquals('12:00~', ETAConverter::toDigits('NOON'));
        $this->assertEquals('13:00~', ETAConverter::toDigits('EPM'));
        $this->assertEquals('16:00~', ETAConverter::toDigits('PM'));
        $this->assertEquals('16:00~', ETAConverter::toDigits('APREM'));
        $this->assertEquals('16:00~', ETAConverter::toDigits('APRÈM'));
        $this->assertEquals('16:00~', ETAConverter::toDigits('APRÈS-MIDI'));
        $this->assertEquals('16:00~', ETAConverter::toDigits('APRES-MIDI'));
        $this->assertEquals('16:00~', ETAConverter::toDigits('APRES MIDI'));
        $this->assertEquals('16:00~', ETAConverter::toDigits('APRÈS MIDI'));
        $this->assertEquals('20:00~', ETAConverter::toDigits('SOIR'));
        $this->assertEquals('20:00~', ETAConverter::toDigits('EVENING'));
        $this->assertEquals('20:00~', ETAConverter::toDigits('EVE'));
        $this->assertEquals('22:00~', ETAConverter::toDigits('LPM'));
        $this->assertEquals('24:00~', ETAConverter::toDigits('MINUIT'));
    }

    public function testToDigitsWithInvalidTime(): void
    {
        $this->assertEquals('INVALID', ETAConverter::toDigits('INVALID'));
    }

    public function testToDigitsWithNonMatchingTime(): void
    {
        $this->assertEquals('21:00', ETAConverter::toDigits('21:00'));
    }

    public function testToDigitsWithEmptyTime(): void
    {
        $this->assertEquals('', ETAConverter::toDigits(''));
    }

    public function testToLetters(): void
    {
        $this->assertEquals('EAM', ETAConverter::toLetters('02:00~'));
        $this->assertEquals('NUIT', ETAConverter::toLetters('03:00~'));
        $this->assertEquals('AM', ETAConverter::toLetters('06:00~'));
        $this->assertEquals('LAM', ETAConverter::toLetters('10:00~'));
        $this->assertEquals('NOON', ETAConverter::toLetters('12:00~'));
        $this->assertEquals('EPM', ETAConverter::toLetters('13:00~'));
        $this->assertEquals('PM', ETAConverter::toLetters('16:00~'));
        $this->assertEquals('SOIR', ETAConverter::toLetters('20:00~'));
        $this->assertEquals('LPM', ETAConverter::toLetters('22:00~'));
        $this->assertEquals('MINUIT', ETAConverter::toLetters('24:00~'));
    }

    public function testToLettersWithInvalidTime(): void
    {
        $this->assertEquals('INVALID', ETAConverter::toLetters('INVALID'));
    }

    public function testToLettersWithNonMatchingTime(): void
    {
        $this->assertEquals('21:00', ETAConverter::toLetters('21:00'));
    }

    public function testToLettersWithEmptyTime(): void
    {
        $this->assertEquals('', ETAConverter::toLetters(''));
    }
}
