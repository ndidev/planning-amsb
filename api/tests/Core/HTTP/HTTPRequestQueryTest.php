<?php

// Path: api/tests/Core/HTTP/HTTPRequestQueryTest.php

declare(strict_types=1);

namespace App\Tests\Core\HTTP;

use App\Core\HTTP\HTTPRequestQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HTTPRequestQuery::class)]
final class HTTPRequestQueryTest extends TestCase
{
    public function testBuildWithGet(): void
    {
        // Given
        $_GET = [
            "param1" => "value1",
        ];
        $query = new HTTPRequestQuery();

        // When
        $reflectionClass = new \ReflectionClass($query);
        $store = $reflectionClass->getProperty("store")->getValue($query);

        // Then
        $this->assertSame($_GET, $store);
    }

    public function testBuildWithQueryString(): void
    {
        // Given
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/path?param1=value1&param2=42&param3=&param4';
        $query = new HTTPRequestQuery();
        $expected = [
            "param1" => "value1",
            "param2" => "42",
            "param3" => "",
            "param4" => "",
        ];

        // When
        $reflectionClass = new \ReflectionClass($query);
        $store = $reflectionClass->getProperty("store")->getValue($query);

        // Then
        $this->assertSame($expected, $store);
    }

    #[\Override]
    public static function tearDownAfterClass(): void
    {
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '';
    }
}
