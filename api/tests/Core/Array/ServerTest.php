<?php

// Path: api/tests/Core/Array/ServerTest.php

declare(strict_types=1);

namespace App\Tests\Core\Globals;

use App\Core\Array\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Server::class)]
final class ServerTest extends TestCase
{
    public function testGet(): void
    {
        // Given
        $_SERVER['fooFromServer'] = 'bar';

        // When
        $fooValue = Server::get('fooFromServer');
        $bazValue = Server::get('bazFromServer');

        // Then
        $this->assertSame('bar', $fooValue);
        $this->assertNull($bazValue);
    }
}
