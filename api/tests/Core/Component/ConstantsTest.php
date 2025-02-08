<?php

// Path: api/tests/Core/Component/ConstantsTest.php

declare(strict_types=1);

namespace App\Tests\Core\Component;

use PHPUnit\Framework\TestCase;

use const App\Core\Component\Constants\{
    ONE_SECOND,
    ONE_MINUTE,
    ONE_HOUR,
    ONE_DAY,
    ONE_WEEK,
    ONE_YEAR
};

final class ConstantsTest extends TestCase
{
    public function testConstantsShouldReturnTheNumberOfSecondsInPeriod(): void
    {
        // Given
        $expected = [
            'ONE_SECOND' => 1,
            'ONE_MINUTE' => 60,
            'ONE_HOUR'   => 60 * 60,
            'ONE_DAY'    => 60 * 60 * 24,
            'ONE_WEEK'   => 60 * 60 * 24 * 7,
            'ONE_YEAR'   => 60 * 60 * 24 * 365,
        ];

        // When
        $actual = [
            'ONE_SECOND' => ONE_SECOND,
            'ONE_MINUTE' => ONE_MINUTE,
            'ONE_HOUR' => ONE_HOUR,
            'ONE_DAY' => ONE_DAY,
            'ONE_WEEK' => ONE_WEEK,
            'ONE_YEAR' => ONE_YEAR,
        ];

        // Then
        $this->assertSame($expected, $actual);
    }
}
