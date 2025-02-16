<?php

// Path: api/tests/Entity/Config/PdfConfigTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Config;

use App\Entity\Config\PdfConfig;
use App\Entity\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfConfig::class)]
#[UsesClass(ThirdParty::class)]
final class PdfConfigTest extends TestCase
{
    public function testSetAndGetSupplier(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $supplier = new ThirdParty();

        // When
        $pdfConfig->setSupplier($supplier);
        $actualSupplier = $pdfConfig->getSupplier();

        // Then
        $this->assertSame($supplier, $actualSupplier);
    }

    public function testIsAutoSend(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $autoSend = true;

        // When
        $pdfConfig->setAutoSend($autoSend);
        $actualAutoSend = $pdfConfig->isAutoSend();

        // Then
        $this->assertSame($autoSend, $actualAutoSend);
    }

    public function testSetAndGetEmailsFromArray(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $emails = ['email1', 'email2'];

        // When
        $pdfConfig->setEmails($emails);
        $actualEmails = $pdfConfig->getEmails();

        // Then
        $this->assertSame($emails, $actualEmails);
    }

    public function testSetAndGetEmailsFromString(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $emails = 'email1' . PHP_EOL . 'email2';
        $expectedEmails = ['email1', 'email2'];

        // When
        $pdfConfig->setEmails($emails);
        $actualEmails = $pdfConfig->getEmails();

        // Then
        $this->assertSame($expectedEmails, $actualEmails);
    }

    public function testGetEmailsAsString(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $emails = ['email1', 'email2'];
        $expectedEmailsAsString = 'email1' . PHP_EOL . 'email2';

        // When
        $pdfConfig->setEmails($emails);
        $actualEmailsAsString = $pdfConfig->getEmailsAsString();

        // Then
        $this->assertSame($expectedEmailsAsString, $actualEmailsAsString);
    }

    public function testSetAndGetDaysBefore(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $daysBefore = 5;

        // When
        $pdfConfig->setDaysBefore($daysBefore);
        $actualDaysBefore = $pdfConfig->getDaysBefore();

        // Then
        $this->assertSame($daysBefore, $actualDaysBefore);
    }

    public function testSetAndGetDaysAfter(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $daysAfter = 5;

        // When
        $pdfConfig->setDaysAfter($daysAfter);
        $actualDaysAfter = $pdfConfig->getDaysAfter();

        // Then
        $this->assertSame($daysAfter, $actualDaysAfter);
    }

    public function testToArray(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $id = 1;
        $module = 'bois';
        $supplierId = 10;
        $autoSend = true;
        $emails = ['email1', 'email2'];
        $daysBefore = 5;
        $daysAfter = 5;

        $pdfConfig
            ->setId($id)
            ->setSupplier((new ThirdParty())->setId($supplierId))
            ->setAutoSend($autoSend)
            ->setEmails($emails)
            ->setDaysBefore($daysBefore)
            ->setDaysAfter($daysAfter);
        $pdfConfig->module = $module;

        $expectedArray = [
            'id' => $id,
            'module' => $module,
            'fournisseur' => $supplierId,
            'envoi_auto' => $autoSend,
            'liste_emails' => 'email1' . PHP_EOL . 'email2',
            'jours_avant' => $daysBefore,
            'jours_apres' => $daysAfter,
        ];

        // When
        $actualArray = $pdfConfig->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
