<?php

// path: api/tests/Core/PDF/PDFMailerTest.php

namespace App\Tests\Core\PDF;

use App\Core\PDF\PDFMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PDFMailer::class)]
class PDFMailerTest extends TestCase
{
    public function testAddAddresses(): void
    {
        $pdf = $this->createStub(\tFPDF::class);
        $pdfMailer = new PDFMailer($pdf, new \DateTime(), new \DateTime(), [], false);

        $pdfMailer->addAddresses();

        $this->assertNotEmpty($pdfMailer->getToAddresses());
        $this->assertNotEmpty($pdfMailer->getCcAddresses());
        $this->assertNotEmpty($pdfMailer->getBccAddresses());
    }
}
