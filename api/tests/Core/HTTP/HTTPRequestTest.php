<?php

// Path: api/tests/Core/HTTP/HTTPRequestTest.php

namespace App\Tests\Core\HTTP;

use App\Core\HTTP\HTTPRequest;
use App\Core\HTTP\HTTPRequestQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HTTPRequest::class)]
#[UsesClass(HTTPRequestQuery::class)]
final class HTTPRequestTest extends TestCase
{
    public function testGetMethod(): void
    {
        // Given
        $_SERVER["REQUEST_METHOD"] = "GET";
        $request = new HTTPRequest();

        // When
        $method = $request->getMethod();

        // Then
        $this->assertSame("GET", $method);
    }

    public function testGetQuery(): void
    {
        // Given
        $request = new HTTPRequest();

        // When
        $query = $request->getQuery();

        // Then
        $this->assertInstanceOf(HTTPRequestQuery::class, $query);
    }

    public function testGetQueryParams(): void
    {
        // Given
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REQUEST_URI"] = "/api/v1/pdf?config=1&date_debut=2021-01-01&date_fin=";
        $request = new HTTPRequest();

        // When
        $queryArray =
            (new \ReflectionClass(HTTPRequestQuery::class))
            ->getProperty("query")
            ->getValue($request->getQuery());

        // Then
        $this->assertSame([
            "config" => "1",
            "date_debut" => "2021-01-01",
            "date_fin" => "",
        ], $queryArray);
    }

    public function testGetBody(): void
    {
        // Given
        $_SERVER["REQUEST_METHOD"] = "POST";
        $_SERVER["REQUEST_URI"] = "/api/v1/pdf";
        $body = [
            "config" => 1,
            "date_debut" => "2021-01-01",
            "date_fin" => "",
        ];
        $_POST = $body;
        $request = new HTTPRequest();

        // When
        $requestBody = $request->getBody();

        // Then
        $this->assertSame($body, $requestBody);
    }
}
