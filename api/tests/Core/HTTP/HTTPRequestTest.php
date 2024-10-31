<?php

// Path: api/tests/Core/HTTP/HTTPRequestTest.php

namespace App\Tests\Core\HTTP;

use App\Core\HTTP\HTTPRequest;
use PHPUnit\Framework\TestCase;

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
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REQUEST_URI"] = "/api/v1/pdf?config=1&date_debut=2021-01-01&date_fin=";
        $request = new HTTPRequest();

        // When
        $query = $request->getQuery();

        // Then
        $this->assertSame([
            "config" => "1",
            "date_debut" => "2021-01-01",
            "date_fin" => "",
        ], $query);
    }

    public function testGetQueryParams(): void
    {
        // Given
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REQUEST_URI"] = "/api/v1/pdf?config=1&date_debut=2021-01-01&date_fin=";
        $request = new HTTPRequest();

        // When
        // Set value as integer
        $configId = $request->getQueryParam("config", type: "int");
        $expectedConfigId = 1;
        // Unset value as null
        $missingParam = $request->getQueryParam("missing_param", type: "int");
        // Set value as datetime
        $startDate = $request->getQueryParam("date_debut", type: "datetime");
        $expectedStartDate = (new \DateTime("2021-01-01"))->setTime(0, 0, 0);
        // Set and empty value as datetime
        $endDate = $request->getQueryParam("date_fin", "now", "datetime")->setTime(0, 0, 0);
        $expectedEndDate = (new \DateTime())->setTime(0, 0, 0);
        // Unset value as boolean
        $archive = $request->getQueryParam("archive", false, "bool");
        $expectedArchive = false;

        // Then
        $this->assertSame($expectedConfigId, $configId);
        $this->assertNull($missingParam);
        $this->assertEquals($expectedStartDate, $startDate);
        $this->assertEquals($expectedEndDate, $endDate);
        $this->assertSame($expectedArchive, $archive);
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
