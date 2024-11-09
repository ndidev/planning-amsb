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
    public function testSetName(): void
    {
        // Given
        $bulkQuality = new BulkQuality();
        $name = "High Quality";

        // When
        $bulkQuality->setName($name);

        // Then
        $this->assertEquals($name, $bulkQuality->getName());
    }

    public function testSetColor(): void
    {
        // Given
        $bulkQuality = new BulkQuality();
        $color = "Red";

        // When
        $bulkQuality->setColor($color);

        // Then
        $this->assertEquals($color, $bulkQuality->getColor());
    }

    public function testSetProduct(): void
    {
        // Given
        $bulkQuality = new BulkQuality();
        $bulkProduct = $this->createMock(BulkProduct::class);

        // When
        $bulkQuality->setProduct($bulkProduct);

        // Then
        $this->assertSame($bulkProduct, $bulkQuality->getProduct());
    }

    public function testToArray(): void
    {
        // Given
        $bulkQuality = new BulkQuality();
        $bulkQuality->setName("High Quality");
        $bulkQuality->setColor("Red");

        // When
        $array = $bulkQuality->toArray();

        // Then
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('nom', $array);
        $this->assertArrayHasKey('couleur', $array);
        $this->assertEquals("High Quality", $array['nom']);
        $this->assertEquals("Red", $array['couleur']);
    }
}
