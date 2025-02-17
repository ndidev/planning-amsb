<?php

// Path: api/tests/Entity/Bulk/BulkProductTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Bulk;

use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BulkProduct::class)]
#[UsesClass(BulkQuality::class)]
class BulkProductTest extends TestCase
{
    public function testSetAndGetColor(): void
    {
        // Given
        $bulkProduct = new BulkProduct();
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
        $bulkProduct = new BulkProduct();
        $expectedColor = BulkProduct::DEFAULT_COLOR;

        // When
        $bulkProduct->color = '';
        $actualColor = $bulkProduct->color;

        // Then
        $this->assertEquals($expectedColor, $actualColor);
    }

    public function testSetAndGetQualities(): void
    {
        // Given
        $bulkProduct = new BulkProduct();
        $quality1 = new BulkQuality();
        $quality2 = new BulkQuality();

        // When
        $bulkProduct->qualities = [$quality1, $quality2];

        // Then
        $this->assertCount(2, $bulkProduct->qualities);
        $this->assertSame($quality1, $bulkProduct->qualities[0]);
        $this->assertSame($quality2, $bulkProduct->qualities[1]);
        $this->assertSame($bulkProduct, $quality1->product);
        $this->assertSame($bulkProduct, $quality2->product);
    }

    public function testToArray(): void
    {
        // Given
        $bulkProduct = new BulkProduct();
        $bulkProduct->id = 1;
        $bulkProduct->name = "Test Product";
        $bulkProduct->color = "Red";
        $bulkProduct->unit = "kg";

        $quality = new BulkQuality();
        $quality->id = 1;
        $quality->name = 'high';
        $quality->color = 'Red';

        $bulkProduct->qualities = [$quality];

        $expectedArray = [
            "id" => 1,
            "nom" => "Test Product",
            "couleur" => "Red",
            "unite" => "kg",
            "qualites" => [
                ['id' => 1, 'nom' => 'high', 'couleur' => 'Red']
            ],
        ];

        // When
        $resultArray = $bulkProduct->toArray();

        // Then
        $this->assertEquals($expectedArray, $resultArray);
    }
}
