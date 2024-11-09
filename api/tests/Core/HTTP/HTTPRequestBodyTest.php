<?php

// Path: api/tests/Core/HTTP/HTTPRequestBodyTest.php

declare(strict_types=1);

namespace App\Tests\Core\HTTP;

use App\Core\HTTP\HTTPRequestBody;
use App\Core\HTTP\RequestParameterStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HTTPRequestBody::class)]
#[CoversClass(RequestParameterStore::class)]
final class HTTPRequestBodyTest extends TestCase
{
    public function testInstanciation(): void
    {
        // Given
        $body = HTTPRequestBody::buildFromRequest();

        // Then
        $this->assertInstanceOf(HTTPRequestBody::class, $body);
    }

    public function testBuildWithPost(): void
    {
        // Given
        $_POST = [
            'key' => 'value',
        ];
        $body = HTTPRequestBody::buildFromRequest();

        // When
        $reflectionClass = new \ReflectionClass($body);
        $parameterBag = $reflectionClass->getProperty("parameterBag")->getValue($body);

        // Then
        $this->assertSame($_POST, $parameterBag);
    }

    public function testBuildWithPhpInput(): void
    {
        // Given
        $body = HTTPRequestBody::buildFromRequest();

        // When
        $reflectionClass = new \ReflectionClass($body);
        $parameterBag = $reflectionClass->getProperty("parameterBag")->getValue($body);

        // Then
        $this->assertIsArray($parameterBag);
    }

    public function testIsEmpty(): void
    {
        // Given
        $body = HTTPRequestBody::buildFromRequest();

        // Then
        $this->assertTrue($body->isEmpty());
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_POST = [];
    }
}
