<?php

// Path: api/tests/Entity/Config/InfoBannerLineTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Config;

use App\Entity\Config\InfoBannerLine;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InfoBannerLine::class)]
final class InfoBannerLineTest extends TestCase
{
    public function testSetAndGetModule(): void
    {
        // Given
        $infoBannerLine = new InfoBannerLine();
        $module = 'bois';

        // When
        $infoBannerLine->setModule($module);
        $actualModule = $infoBannerLine->module;

        // Then
        $this->assertSame($module, $actualModule);
    }

    public function testSetAndGetColor(): void
    {
        // Given
        $infoBannerLine = new InfoBannerLine();
        $color = 'color';

        // When
        $infoBannerLine->color = $color;
        $actualColor = $infoBannerLine->color;

        // Then
        $this->assertSame($color, $actualColor);
    }

    public function testSetAndGetEmptyColor(): void
    {
        // Given
        $infoBannerLine = new InfoBannerLine();
        $color = '';
        $expected = InfoBannerLine::DEFAULT_COLOR;

        // When
        $infoBannerLine->color = $color;
        $actualColor = $infoBannerLine->color;

        // Then
        $this->assertSame($expected, $actualColor);
    }

    public function testToArray(): void
    {
        // Given
        $infoBannerLine = new InfoBannerLine();
        $infoBannerLine->id = 1;
        $infoBannerLine->module = 'bois';
        $infoBannerLine->isDisplayedOnPC = true;
        $infoBannerLine->isDisplayedOnTV = true;
        $infoBannerLine->message = 'message';
        $infoBannerLine->color = 'color';

        // When
        $actualArray = $infoBannerLine->toArray();

        // Then
        $this->assertSame(
            [
                'id' => 1,
                'module' => 'bois',
                'pc' => true,
                'tv' => true,
                'message' => 'message',
                'couleur' => 'color',
            ],
            $actualArray
        );
    }
}
