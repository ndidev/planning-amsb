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
    public function testSetAndGetName(): void
    {
        // Given
        $bulkProduct = new BulkProduct();

        // When
        $bulkProduct->setName("Test Product");

        // Then
        $this->assertEquals("Test Product", $bulkProduct->getName());
    }

    public function testSetAndGetColor(): void
    {
        // Given
        $bulkProduct = new BulkProduct();

        // When
        $bulkProduct->setColor("Red");

        // Then
        $this->assertEquals("Red", $bulkProduct->getColor());
    }

    public function testSetAndGetUnit(): void
    {
        // Given
        $bulkProduct = new BulkProduct();

        // When
        $bulkProduct->setUnit("kg");

        // Then
        $this->assertEquals("kg", $bulkProduct->getUnit());
    }

    public function testSetAndGetQualities(): void
    {
        // Given
        $bulkProduct = new BulkProduct();
        $quality1 = $this->createMock(BulkQuality::class);
        $quality2 = $this->createMock(BulkQuality::class);

        $quality1->expects($this->once())
            ->method('setProduct')
            ->with($bulkProduct);

        $quality2->expects($this->once())
            ->method('setProduct')
            ->with($bulkProduct);

        // When
        $bulkProduct->setQualities([$quality1, $quality2]);

        // Then
        $this->assertCount(2, $bulkProduct->getQualities());
        $this->assertSame($quality1, $bulkProduct->getQualities()[0]);
        $this->assertSame($quality2, $bulkProduct->getQualities()[1]);
    }

    public function testToArray(): void
    {
        // Given
        $bulkProduct = new BulkProduct();
        $bulkProduct->setId(1)
            ->setName("Test Product")
            ->setColor("Red")
            ->setUnit("kg");

        $quality = $this->createMock(BulkQuality::class);
        $quality->expects($this->once())
            ->method('toArray')
            ->willReturn(['quality' => 'high']);

        $bulkProduct->setQualities([$quality]);

        $expectedArray = [
            "id" => 1,
            "nom" => "Test Product",
            "couleur" => "Red",
            "unite" => "kg",
            "qualites" => [
                ['quality' => 'high']
            ],
        ];

        // When
        $resultArray = $bulkProduct->toArray();

        // Then
        $this->assertEquals($expectedArray, $resultArray);
    }
}
