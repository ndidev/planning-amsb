<?php

// Path: api/tests/Entity/Bulk/BulkQualityTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use App\Entity\Bulk\BulkQuality;
use App\Entity\Bulk\BulkProduct;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkQuality::class)]
#[UsesClass(BulkProduct::class)]
class BulkQualityTest extends TestCase
{
    public function testSetAndGetColor(): void
    {
        // Given
        $bulkProduct = new BulkQuality();
        $color = 'Red';

        // When
        $bulkProduct->color = $color;
        $actualColor = $bulkProduct->color;

        // Then
        $this->assertEquals($color, $actualColor);
    }

    public function testSetAndGetEmptyColor(): void
    {
        // Given
        $bulkProduct = new BulkQuality();
        $expectedColor = BulkQuality::DEFAULT_COLOR;

        // When
        $bulkProduct->color = '';
        $actualColor = $bulkProduct->color;

        // Then
        $this->assertEquals($expectedColor, $actualColor);
    }

    public function testToArray(): void
    {
        // Given
        $bulkQuality = new BulkQuality();
        $bulkQuality->id = 1;
        $bulkQuality->name = "High Quality";
        $bulkQuality->color = "Red";

        $expectedArray = [
            'id' => 1,
            'nom' => "High Quality",
            'couleur' => "Red",
        ];

        // When
        $actualArray = $bulkQuality->toArray();

        // Then
        $this->assertEquals($expectedArray, $actualArray);
    }
}
