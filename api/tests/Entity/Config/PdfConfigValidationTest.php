<?php

declare(strict_types=1);

namespace App\Tests\Entity\Config;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\Config\PdfConfig;
use App\Entity\ThirdParty\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfConfig::class)]
#[UsesClass(ThirdParty::class)]
final class PdfConfigValidationTest extends TestCase
{
    public function testExpectValidationExceptionOnNewConfig(): void
    {
        // Given
        $pdfConfig = new PdfConfig();

        // Then
        $this->expectException(ValidationException::class);

        // When
        $pdfConfig->validate();
    }

    public function testNoExceptionOnValidConfig(): void
    {
        // Given
        $pdfConfig = self::makeValidConfig();

        // When
        $pdfConfig->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExpectValidationExceptionWhenSupplierIsNull(): void
    {
        // Given
        $pdfConfig = self::makeValidConfig();
        $pdfConfig->supplier = null;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $pdfConfig->validate();
    }

    public function testExpectValidationExceptionWhenDaysBeforeIsNegative(): void
    {
        // Given
        $pdfConfig = self::makeValidConfig();
        $pdfConfig->daysBefore = -1;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $pdfConfig->validate();
    }

    public function testExpectValidationExceptionWhenDaysAfterIsNegative(): void
    {
        // Given
        $pdfConfig = self::makeValidConfig();
        $pdfConfig->daysAfter = -1;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $pdfConfig->validate();
    }

    private static function makeValidConfig(): PdfConfig
    {
        $supplier = new ThirdParty();
        $supplier->id = 1;

        $pdfConfig = new PdfConfig();
        $pdfConfig->supplier = $supplier;
        $pdfConfig->autoSend = false;
        $pdfConfig->emails = ['test@example.com'];
        $pdfConfig->daysBefore = 0;
        $pdfConfig->daysAfter = 0;

        return $pdfConfig;
    }
}
