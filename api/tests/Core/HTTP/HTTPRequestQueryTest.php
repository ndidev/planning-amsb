<?php

// Path: api/tests/Core/HTTP/HTTPRequestQueryTest.php

declare(strict_types=1);

namespace App\Tests\Core\HTTP;

use App\Core\HTTP\HTTPRequestQuery;
use App\Core\HTTP\RequestParameterStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HTTPRequestQuery::class)]
#[CoversClass(RequestParameterStore::class)]
final class HTTPRequestQueryTest extends TestCase
{
    public function testInstanciation(): void
    {
        // Given
        $query = HTTPRequestQuery::buildFromRequest();

        // Then
        $this->assertInstanceOf(HTTPRequestQuery::class, $query);
    }

    public function testBuildWithGet(): void
    {
        // Given
        $_GET = [
            "param1" => "value1",
        ];
        $query = HTTPRequestQuery::buildFromRequest();

        // When
        $reflectionClass = new \ReflectionClass($query);
        $parameterBag = $reflectionClass->getProperty("parameterBag")->getValue($query);

        // Then
        $this->assertSame($_GET, $parameterBag);
    }

    public function testBuildWithQueryString(): void
    {
        // Given
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/path?param1=value1&param2=42&param3=&param4';
        $query = HTTPRequestQuery::buildFromRequest();
        $expected = [
            "param1" => "value1",
            "param2" => "42",
            "param3" => "",
            "param4" => "",
        ];

        // When
        $reflectionClass = new \ReflectionClass($query);
        $parameterBag = $reflectionClass->getProperty("parameterBag")->getValue($query);

        // Then
        $this->assertSame($expected, $parameterBag);
    }

    #[\Override]
    public static function tearDownAfterClass(): void
    {
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '';
    }
}
