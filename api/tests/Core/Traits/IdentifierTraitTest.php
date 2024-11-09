<?php

// Path: api/tests/Core/Traits/IdentifierTraitTest.php

declare(strict_types=1);

namespace App\Tests\Core\Traits;

use App\Core\Traits\IdentifierTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IdentifierTrait::class)]
class IdentifierTraitTest extends TestCase
{
    public function testIdIsNullOnInstanciation(): void
    {
        // Given
        $standardClass = new class {
            use IdentifierTrait;
        };

        // Then
        $this->assertNull($standardClass->getId());
    }

    public function testSetAndGetId(): void
    {
        // Given
        $standardClass = new class {
            use IdentifierTrait;
        };

        // When
        $standardClass->setId(1);

        // Then
        $this->assertEquals(1, $standardClass->getId());
    }
}
