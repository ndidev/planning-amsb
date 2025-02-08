<?php

// Path: api/tests/Entity/Timber/TimberAppointmentValidationTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Timber;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\ThirdParty;
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
        $timberAppointment->setDate(null);
        $timberAppointment->setOnHold(false);

        // Then
        $this->expectException(ValidationException::class);

        // When
        $timberAppointment->validate();
    }

    public function testExpectValidationExceptionWhenSupplierIsNull(): void
    {
        // Given
        $timberAppointment = self::makeValidAppointment();
        $timberAppointment->setSupplier(null);

        // Then
        $this->expectException(ValidationException::class);

        // When
        $timberAppointment->validate();
    }

    public function testExpectValidationExceptionWhenLoadingPlaceIsNull(): void
    {
        // Given
        $timberAppointment = self::makeValidAppointment();
        $timberAppointment->setLoadingPlace(null);

        // Then
        $this->expectException(ValidationException::class);

        // When
        $timberAppointment->validate();
    }

    public function testExpectValidationExceptionWhenCustomerIsNull(): void
    {
        // Given
        $timberAppointment = self::makeValidAppointment();
        $timberAppointment->setCustomer(null);

        // Then
        $this->expectException(ValidationException::class);

        // When
        $timberAppointment->validate();
    }

    private static function makeValidAppointment(): TimberAppointment
    {
        return (new TimberAppointment())
            ->setDate(new \DateTimeImmutable('2021-01-01'))
            ->setOnHold(false)
            ->setSupplier(new ThirdParty())
            ->setLoadingPlace(new ThirdParty())
            ->setCustomer(new ThirdParty());
    }
}
