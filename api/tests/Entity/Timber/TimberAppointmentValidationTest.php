<?php

// Path: api/tests/Entity/Timber/TimberAppointmentValidationTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Timber;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\ThirdParty\ThirdParty;
use App\Entity\Timber\TimberAppointment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberAppointment::class)]
#[UsesClass(ThirdParty::class)]
final class TimberAppointmentValidationTest extends TestCase
{
    public function testExpectValidationExceptionOnNewAppointment(): void
    {
        // Given
        $timberAppointment = new TimberAppointment();

        // Then
        $this->expectException(ValidationException::class);

        // When
        $timberAppointment->validate();
    }

    public function testNoExceptionOnValidAppointment(): void
    {
        // Given
        $timberAppointment = self::makeValidAppointment();

        // When
        $timberAppointment->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExpectValidationExceptionWhenDateIsNullAndAppointmentIsNotOnHold(): void
    {
        // Given
        $timberAppointment = self::makeValidAppointment();
        $timberAppointment->date = null;
        $timberAppointment->isOnHold = false;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $timberAppointment->validate();
    }

    public function testExpectValidationExceptionWhenSupplierIsNull(): void
    {
        // Given
        $timberAppointment = self::makeValidAppointment();
        $timberAppointment->supplier = null;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $timberAppointment->validate();
    }

    public function testExpectValidationExceptionWhenLoadingPlaceIsNull(): void
    {
        // Given
        $timberAppointment = self::makeValidAppointment();
        $timberAppointment->loadingPlace = null;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $timberAppointment->validate();
    }

    public function testExpectValidationExceptionWhenCustomerIsNull(): void
    {
        // Given
        $timberAppointment = self::makeValidAppointment();
        $timberAppointment->customer = null;

        // Then
        $this->expectException(ValidationException::class);

        // When
        $timberAppointment->validate();
    }

    private static function makeValidAppointment(): TimberAppointment
    {
        $appointment = new TimberAppointment();
        $appointment->date = new \DateTimeImmutable('2021-01-01');
        $appointment->isOnHold = false;
        $appointment->supplier = new ThirdParty();
        $appointment->loadingPlace = new ThirdParty();
        $appointment->customer = new ThirdParty();

        return $appointment;
    }
}
