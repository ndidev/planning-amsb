<?php

// Path: api/tests/Entity/Bulk/BulkAppointmentValidationTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Entity\ThirdParty\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkAppointment::class)]
#[UsesClass(BulkProduct::class)]
#[UsesClass(BulkQuality::class)]
#[UsesClass(ThirdParty::class)]
class BulkAppointmentValidationTest extends TestCase
{
    public function testExpectValidationExceptionOnNewAppointment(): void
    {
        // Given
        $appointment = new BulkAppointment();

        // Then
        $this->expectException(ValidationException::class);

        // When
        $appointment->validate();
    }

    public function testNoExceptionOnValidApppointment(): void
    {
        // Given
        $appointment = $this->makeValidAppointment();

        // When
        $appointment->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExpectValidationExceptionWhenProductIsNull(): void
    {
        // Given
        $appointment = $this->makeValidAppointment();
        $appointment->product = null;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $appointment->validate();
    }

    public function testExpectValidationExceptionWhenQuantityIsNegative(): void
    {
        // Given
        $appointment = $this->makeValidAppointment();
        $appointment->quantityValue = -1;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $appointment->validate();
    }

    public function testExpectValidationExceptionWhenSupplierIsNull(): void
    {
        // Given
        $appointment = $this->makeValidAppointment();
        $appointment->supplier = null;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $appointment->validate();
    }

    public function testExpectValidationExceptionWhenCustomerIsNull(): void
    {
        // Given
        $appointment = $this->makeValidAppointment();
        $appointment->customer = null;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $appointment->validate();
    }

    private function makeValidAppointment(): BulkAppointment
    {
        $bulkAppointment = new BulkAppointment();
        $bulkAppointment->date = '2021-01-01';
        $bulkAppointment->product = new BulkProduct();
        $bulkAppointment->quantityValue = 1;
        $bulkAppointment->supplier = new ThirdParty();
        $bulkAppointment->customer = new ThirdParty();

        return $bulkAppointment;
    }
}
