<?php

// Path: api/tests/Core/Component/ColorConverterTest.php

declare(strict_types=1);

namespace App\Tests\Core\Component;

use App\Core\Component\ColorConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorConverter::class)]
final class ColorConverterTest extends TestCase
{
    public function testRgbToHexWithValidColor(): void
    {
        // Given
        $rgb = '255, 255, 255';

        // When
        $actual = ColorConverter::rgbToHex($rgb);

        // Then
        $this->assertSame('#FFFFFF', $actual);
    }

    public function testRgbToHexWithInvalidColor(): void
    {
        // Given
        $rgb = '255, 255, 256';

        // When
        $actual = ColorConverter::rgbToHex($rgb);

        // Then
        $this->assertSame('#000000', $actual);
    }

    public function testRgbToHexWithWrongNumberOfValues(): void
    {
        // Given
        $rgb = '255, 255';

        // When
        $actual = ColorConverter::rgbToHex($rgb);

        // Then
        $this->assertSame('#000000', $actual);
    }

    public function testHexToRgbWithValidColor(): void
    {
        // Given
        $hex = '#FFFFFF';

        // When
        $actual = ColorConverter::hexToRgb($hex);

        // Then
        $this->assertSame('255,255,255', $actual);
    }

    public function testHexToRgbWithInvalidColor(): void
    {
        // Given
        $hex = '#FFFFFG';

        // When
        $actual = ColorConverter::hexToRgb($hex);

        // Then
        $this->assertSame('0,0,0', $actual);
    }
}
