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
        $actualModule = $infoBannerLine->getModule();

        // Then
        $this->assertSame($module, $actualModule);
    }

    public function testSetAndGetPc(): void
    {
        // Given
        $infoBannerLine = new InfoBannerLine();
        $pc = true;

        // When
        $infoBannerLine->setPc($pc);
        $actualPc = $infoBannerLine->isPc();

        // Then
        $this->assertSame($pc, $actualPc);
    }

    public function testSetAndGetTv(): void
    {
        // Given
        $infoBannerLine = new InfoBannerLine();
        $tv = true;

        // When
        $infoBannerLine->setTv($tv);
        $actualTv = $infoBannerLine->isTv();

        // Then
        $this->assertSame($tv, $actualTv);
    }

    public function testSetAndGetMessage(): void
    {
        // Given
        $infoBannerLine = new InfoBannerLine();
        $message = 'message';

        // When
        $infoBannerLine->setMessage($message);
        $actualMessage = $infoBannerLine->getMessage();

        // Then
        $this->assertSame($message, $actualMessage);
    }

    public function testSetAndGetColor(): void
    {
        // Given
        $infoBannerLine = new InfoBannerLine();
        $color = 'color';

        // When
        $infoBannerLine->setColor($color);
        $actualColor = $infoBannerLine->getColor();

        // Then
        $this->assertSame($color, $actualColor);
    }

    public function testToArray(): void
    {
        // Given
        $infoBannerLine = new InfoBannerLine();
        $infoBannerLine->setId(1);
        $infoBannerLine->setModule('bois');
        $infoBannerLine->setPc(true);
        $infoBannerLine->setTv(true);
        $infoBannerLine->setMessage('message');
        $infoBannerLine->setColor('color');

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
