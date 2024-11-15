<?php

// Path: api/tests/Core/HTTP/HTTPRequestBodyTest.php

declare(strict_types=1);

namespace App\Tests\Core\HTTP;

use App\Core\HTTP\HTTPRequestBody;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HTTPRequestBody::class)]
final class HTTPRequestBodyTest extends TestCase
{
    public function testBuildWithPost(): void
    {
        // Given
        $_POST = [
            'key' => 'value',
        ];
        $body = new HTTPRequestBody();

        // When
        $reflectionClass = new \ReflectionClass($body);
        $store = $reflectionClass->getProperty("store")->getValue($body);

        // Then
        $this->assertSame($_POST, $store);
    }

    public function testBuildWithPhpInput(): void
    {
        // Given
        $body = new HTTPRequestBody();

        // When
        $reflectionClass = new \ReflectionClass($body);
        $store = $reflectionClass->getProperty("store")->getValue($body);

        // Then
        $this->assertIsArray($store);
    }

    public function testIsEmpty(): void
    {
        // Given
        $body = new HTTPRequestBody();

        // Then
        $this->assertTrue($body->isEmpty());
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_POST = [];
    }
}
