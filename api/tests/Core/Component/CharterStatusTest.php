<?php

// Path: api/tests/Core/Component/CharterStatusTest.php

declare(strict_types=1);

namespace App\Tests\Core\Component;

use App\Core\Component\CharterStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CharterStatus::class)]
final class CharterStatusTest extends TestCase
{
    public function testTryFromReturnsPendingWhenZeroIsGiven(): void
    {
        // Given
        $temptativeStatus = 0;

        // When
        $actual = CharterStatus::tryFrom($temptativeStatus);

        // Then
        $this->assertSame(CharterStatus::PENDING, $actual);
    }

    public function testTryFromReturnsConfirmedWhenOneIsGiven(): void
    {
        // Given
        $temptativeStatus = 1;

        // When
        $actual = CharterStatus::tryFrom($temptativeStatus);

        // Then
        $this->assertSame(CharterStatus::CONFIRMED, $actual);
    }

    public function testTryFromReturnsCharteredWhenTwoIsGiven(): void
    {
        // Given
        $temptativeStatus = 2;

        // When
        $actual = CharterStatus::tryFrom($temptativeStatus);

        // Then
        $this->assertSame(CharterStatus::CHARTERED, $actual);
    }

    public function testTryFromReturnsLoadedWhenThreeIsGiven(): void
    {
        // Given
        $temptativeStatus = 3;

        // When
        $actual = CharterStatus::tryFrom($temptativeStatus);

        // Then
        $this->assertSame(CharterStatus::LOADED, $actual);
    }

    public function testTryFromReturnsCompletedWhenFourIsGiven(): void
    {
        // Given
        $temptativeStatus = 4;

        // When
        $actual = CharterStatus::tryFrom($temptativeStatus);

        // Then
        $this->assertSame(CharterStatus::COMPLETED, $actual);
    }

    public function testTryFromReturnsPendingWhenUnknownStatusIsGiven(): void
    {
        // Given
        $temptativeStatus = 5;

        // When
        $actual = CharterStatus::tryFrom($temptativeStatus);

        // Then
        $this->assertSame(CharterStatus::PENDING, $actual);
    }
}
