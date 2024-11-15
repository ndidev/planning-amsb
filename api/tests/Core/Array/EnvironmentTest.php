<?php

// Path: api/tests/Core/Array/EnvironmentTest.php

declare(strict_types=1);

namespace App\Tests\Core\Globals;

use App\Core\Array\Environment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Environment::class)]
final class EnvironmentTest extends TestCase
{
    public function testGet(): void
    {
        // Given
        $_ENV['fooFromEnv'] = 'bar';

        // When
        $fooValue = Environment::get('fooFromEnv');
        $bazValue = Environment::get('bazFromEnv');

        // Then
        $this->assertSame('bar', $fooValue);
        $this->assertNull($bazValue);
    }
}
