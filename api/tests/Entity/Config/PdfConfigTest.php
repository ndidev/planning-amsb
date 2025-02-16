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
    public function testIsAutoSendWithBool(): void
    {
        // Given
        $pdfConfig = new PdfConfig();

        // When
        $pdfConfig->autoSend = true;
        $actualAutoSend = $pdfConfig->autoSend;

        // Then
        $this->assertTrue($actualAutoSend);
    }

    public function testIsAutoSendWithInt(): void
    {
        // Given
        $pdfConfig = new PdfConfig();

        // When
        $pdfConfig->autoSend = 1;
        $actualAutoSend = $pdfConfig->autoSend;

        // Then
        $this->assertTrue($actualAutoSend);
    }

    public function testSetAndGetEmailsFromArray(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $emails = ['email1@example.com', 'email2@example.com'];

        // When
        $pdfConfig->emails = $emails;
        $actualEmails = $pdfConfig->emails;

        // Then
        $this->assertSame($emails, $actualEmails);
    }

    public function testSetAndGetEmailsFromString(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $emails = 'email1@example.com' . PHP_EOL . 'email2@example.com';
        $expectedEmails = ['email1@example.com', 'email2@example.com'];

        // When
        $pdfConfig->emails = $emails;
        $actualEmails = $pdfConfig->emails;

        // Then
        $this->assertSame($expectedEmails, $actualEmails);
    }

    public function testGetEmailsAsString(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $emails = ['email1@example.com', 'email2@example.com'];
        $expectedEmailsAsString = 'email1@example.com' . PHP_EOL . 'email2@example.com';

        // When
        $pdfConfig->emails = $emails;
        $actualEmailsAsString = $pdfConfig->getEmailsAsString();

        // Then
        $this->assertSame($expectedEmailsAsString, $actualEmailsAsString);
    }

    public function testToArray(): void
    {
        // Given
        $pdfConfig = new PdfConfig();
        $id = 1;
        $module = 'bois';
        $supplierId = 10;
        $autoSend = true;
        $emails = ['email1@example.com', 'email2@example.com'];
        $daysBefore = 5;
        $daysAfter = 5;

        $pdfConfig->id = $id;
        $pdfConfig->supplier = new ThirdParty()->setId($supplierId);
        $pdfConfig->autoSend = $autoSend;
        $pdfConfig->emails = $emails;
        $pdfConfig->daysBefore = $daysBefore;
        $pdfConfig->daysAfter = $daysAfter;
        $pdfConfig->module = $module;

        $expectedArray = [
            'id' => $id,
            'module' => $module,
            'fournisseur' => $supplierId,
            'envoi_auto' => $autoSend,
            'liste_emails' => 'email1@example.com' . PHP_EOL . 'email2@example.com',
            'jours_avant' => $daysBefore,
            'jours_apres' => $daysAfter,
        ];

        // When
        $actualArray = $pdfConfig->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
