<?php

// Path: api/tests/Core/HTTP/HTTPRequestTest.php

declare(strict_types=1);

namespace App\Tests\Core\HTTP;

use App\Core\HTTP\HTTPRequest;
use App\Core\HTTP\HTTPRequestBody;
use App\Core\HTTP\HTTPRequestQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HTTPRequest::class)]
#[UsesClass(HTTPRequestQuery::class)]
#[UsesClass(HTTPRequestBody::class)]
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

    public function testGetBody(): void
    {
        // Given
        $_SERVER["REQUEST_METHOD"] = "POST";
        $request = new HTTPRequest();

        // When
        $body = $request->getBody();

        // Then
        $this->assertInstanceOf(HTTPRequestBody::class, $body);
    }
}
