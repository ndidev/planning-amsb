<?php

// Path: api/tests/Entity/Bulk/BulkAppointmentValidationTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Entity\ThirdParty;
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
        $appointment->setProduct(null);

        // Then
        $this->expectException(ValidationException::class);

        // When
        $appointment->validate();
    }

    public function testExpectValidationExceptionWhenQuantityIsNegative(): void
    {
        // Given
        $appointment = $this->makeValidAppointment();
        $appointment->setQuantityValue(-1);

        // Then
        $this->expectException(ValidationException::class);

        // When
        $appointment->validate();
    }

    public function testExpectValidationExceptionWhenSupplierIsNull(): void
    {
        // Given
        $appointment = $this->makeValidAppointment();
        $appointment->setSupplier(null);

        // Then
        $this->expectException(ValidationException::class);

        // When
        $appointment->validate();
    }

    public function testExpectValidationExceptionWhenCustomerIsNull(): void
    {
        // Given
        $appointment = $this->makeValidAppointment();
        $appointment->setCustomer(null);

        // Then
        $this->expectException(ValidationException::class);

        // When
        $appointment->validate();
    }

    private function makeValidAppointment(): BulkAppointment
    {
        return (new BulkAppointment())
            ->setDate('2021-01-01')
            ->setProduct(new BulkProduct())
            ->setQuantityValue(1)
            ->setSupplier(new ThirdParty())
            ->setCustomer(new ThirdParty());
    }
}
