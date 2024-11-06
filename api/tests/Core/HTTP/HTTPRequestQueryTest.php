<?php

// Path: api/tests/Core/HTTP/HTTPRequestQueryTest.php

namespace App\Tests\Core\HTTP;

use App\Core\HTTP\HTTPRequestQuery;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HTTPRequestQuery::class)]
final class HTTPRequestQueryTest extends TestCase
{
    public function testGetParam(): void
    {
        // Given
        $query = new HTTPRequestQuery([
            "config" => "1",
            "date_debut" => "2021-01-01",
            "date_fin" => "",
        ]);

        // When
        // Set value as integer
        $configId = $query->getParam("config", type: "int");
        $expectedConfigId = 1;
        // Unset value as null
        $missingParam = $query->getParam("missing_param", type: "int");
        // Set value as datetime
        $startDate = $query->getParam("date_debut", type: "datetime");
        $expectedStartDate = (new \DateTime("2021-01-01"))->setTime(0, 0, 0);
        // Set and empty value as datetime
        $endDate = $query->getParam("date_fin", "now", "datetime")->setTime(0, 0, 0);
        $expectedEndDate = (new \DateTime())->setTime(0, 0, 0);
        // Unset value as boolean
        $archive = $query->getParam("archive", false, "boolean");
        $expectedArchive = false;

        // Then
        $this->assertSame($expectedConfigId, $configId, "Query parameter 'config' should be an integer.");
        $this->assertNull($missingParam, "Query parameter 'missing_param' should be null.");
        $this->assertEquals($expectedStartDate, $startDate, "Query parameter 'date_debut' should be a datetime object.");
        $this->assertEquals($expectedEndDate, $endDate, "Query parameter 'date_fin' should be a datetime object.");
        $this->assertSame($expectedArchive, $archive, "Query parameter 'archive' should be a boolean.");
    }

    // ============================
    // Test cases for string params
    // ============================

    public function testGetSetString(): void
    {
        // Given
        $paramName = 'stringParam';
        $paramValue = 'stringValue';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);


        // When
        $stringParam = $query->getParam($paramName);

        // Then
        $this->assertSame($paramValue, $stringParam);
    }

    public function testGetDefaultWithEmptyString(): void
    {
        // Given
        $paramName = 'emptyStringParam';
        $paramValue = '';
        $defaultValue = 'default';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $stringParam = $query->getParam($paramName, $defaultValue);

        // Then
        $this->assertSame($defaultValue, $stringParam);
    }

    public function testGetAllowedEmptyString(): void
    {
        // Given
        $paramName = 'emptyStringParam';
        $paramValue = '';
        $defaultValue = 'default';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $stringParam = $query->getParam($paramName, $defaultValue, allowEmpty: true);

        // Then
        $this->assertSame($paramValue, $stringParam);
    }

    public function testGetUnsetString(): void
    {
        // Given
        $paramName = 'unsetStringParam';
        $defaultValue = 'default';
        $query = new HTTPRequestQuery([]);

        // When
        $stringParam = $query->getParam($paramName, $defaultValue);

        // Then
        $this->assertSame($defaultValue, $stringParam);
    }

    public function testDefaultStringHasWrongType(): void
    {
        // Given
        $paramName = 'stringParam';
        $paramValue = 'stringValue';
        $defaultValue = 1;
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $query->getParam($paramName, $defaultValue);
    }

    // ============================
    // Test cases for integer params
    // ============================

    public function testGetSetInteger(): void
    {
        // Given
        $paramName = 'integerParam';
        $paramValue = '42';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $integerParam = $query->getParam($paramName, type: 'int');

        // Then
        $this->assertSame(42, $integerParam);
    }

    public function testGetDefaultWithEmptyInteger(): void
    {
        // Given
        $paramName = 'emptyIntegerParam';
        $paramValue = '';
        $defaultValue = 42;
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $integerParam = $query->getParam($paramName, $defaultValue, type: 'int');

        // Then
        $this->assertSame($defaultValue, $integerParam);
    }

    public function testGetAllowedEmptyInteger(): void
    {
        // Given
        $paramName = 'emptyIntegerParam';
        $paramValue = '';
        $defaultValue = 42;
        $expectedValue = 0;
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $integerParam = $query->getParam($paramName, $defaultValue, type: 'int', allowEmpty: true);

        // Then
        $this->assertSame($expectedValue, $integerParam);
    }

    public function testGetUnsetInteger(): void
    {
        // Given
        $paramName = 'unsetIntegerParam';
        $defaultValue = 42;
        $query = new HTTPRequestQuery([]);

        // When
        $integerParam = $query->getParam($paramName, $defaultValue, type: 'int');

        // Then
        $this->assertSame($defaultValue, $integerParam);
    }

    public function testDefaultIntegerHasWrongType(): void
    {
        // Given
        $paramName = 'integerParam';
        $paramValue = '42';
        $defaultValue = 'default';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $query->getParam($paramName, $defaultValue, type: 'int');
    }

    // ============================
    // Test cases for double params
    // ============================

    public function testGetSetDouble(): void
    {
        // Given
        $paramName = 'doubleParam';
        $paramValue = '42.42';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $doubleParam = $query->getParam($paramName, type: 'double');

        // Then
        $this->assertSame(42.42, $doubleParam);
    }

    public function testGetDefaultWithEmptyDouble(): void
    {
        // Given
        $paramName = 'emptyDoubleParam';
        $paramValue = '';
        $defaultValue = 42.42;
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $doubleParam = $query->getParam($paramName, $defaultValue, type: 'double');

        // Then
        $this->assertSame($defaultValue, $doubleParam);
    }

    public function testGetAllowedEmptyDouble(): void
    {
        // Given
        $paramName = 'emptyDoubleParam';
        $paramValue = '';
        $defaultValue = 42.42;
        $expectedValue = 0.0;
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $doubleParam = $query->getParam($paramName, $defaultValue, type: 'double', allowEmpty: true);

        // Then
        $this->assertSame($expectedValue, $doubleParam);
    }

    public function testGetUnsetDouble(): void
    {
        // Given
        $paramName = 'unsetDoubleParam';
        $defaultValue = 42.42;
        $query = new HTTPRequestQuery([]);

        // When
        $doubleParam = $query->getParam($paramName, $defaultValue, type: 'double');

        // Then
        $this->assertSame($defaultValue, $doubleParam);
    }

    public function testDefaultDoubleHasWrongType(): void
    {
        // Given
        $paramName = 'doubleParam';
        $paramValue = '42.42';
        $defaultValue = 'default';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $query->getParam($paramName, $defaultValue, type: 'double');
    }

    // ============================
    // Test cases for boolean params
    // ============================

    public function testGetSetBoolean(): void
    {
        // Given
        $paramName = 'booleanParam';
        $paramValue = 'true';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $booleanParam = $query->getParam($paramName, type: 'boolean');

        // Then
        $this->assertTrue($booleanParam);
    }

    public function testGetDefaultWithEmptyBoolean(): void
    {
        // Given
        $paramName = 'emptyBooleanParam';
        $paramValue = '';
        $defaultValue = true;
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $booleanParam = $query->getParam($paramName, $defaultValue, type: 'boolean');

        // Then
        $this->assertTrue($booleanParam);
    }

    public function testGetAllowedEmptyBoolean(): void
    {
        // Given
        $paramName = 'emptyBooleanParam';
        $paramValue = '';
        $defaultValue = true;
        $expectedValue = false;
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $booleanParam = $query->getParam($paramName, $defaultValue, type: 'boolean', allowEmpty: true);

        // Then
        $this->assertSame($expectedValue, $booleanParam);
    }

    public function testGetUnsetBoolean(): void
    {
        // Given
        $paramName = 'unsetBooleanParam';
        $defaultValue = true;
        $query = new HTTPRequestQuery([]);

        // When
        $booleanParam = $query->getParam($paramName, $defaultValue, type: 'boolean');

        // Then
        $this->assertTrue($booleanParam);
    }

    public function testDefaultBooleanHasWrongType(): void
    {
        // Given
        $paramName = 'booleanParam';
        $paramValue = 'true';
        $defaultValue = 'default';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $query->getParam($paramName, $defaultValue, type: 'boolean');
    }

    // ============================
    // Test cases for datetime params
    // ============================

    public function testGetSetDatetime(): void
    {
        // Given
        $paramName = 'datetimeParam';
        $paramValue = '2021-01-01';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        $datetimeParam = $query->getParam($paramName, type: 'datetime');

        // Then
        $this->assertEquals(new \DateTime('2021-01-01'), $datetimeParam);
    }

    public function testGetDefaultWithEmptyDatetime(): void
    {
        // Given
        $paramName = 'emptyDatetimeParam';
        $paramValue = '';
        $defaultDate = '2021-01-01';
        $defaultValue = new \DateTime($defaultDate);
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        /** @var \DateTimeInterface */
        $datetimeParam = $query->getParam($paramName, $defaultValue, type: 'datetime');

        // Then
        $this->assertEquals($defaultDate, $datetimeParam->format('Y-m-d'));
    }

    public function testGetAllowedEmptyDatetime(): void
    {
        // Given
        $paramName = 'emptyDatetimeParam';
        $paramValue = '';
        $defaultValue = new \DateTime('2021-01-01');
        $expectedDate = (new DateTime())->format('Y-m-d');
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // When
        /** @var \DateTimeInterface */
        $datetimeParam = $query->getParam($paramName, $defaultValue, type: 'datetime', allowEmpty: true);

        // Then
        $this->assertEquals($expectedDate, $datetimeParam->format('Y-m-d'));
    }

    public function testGetUnsetDatetimeWithStringDefault(): void
    {
        // Given
        $paramName = 'unsetDatetimeParam';
        $defaultValue = '2021-01-01';
        $query = new HTTPRequestQuery([]);

        // When
        /** @var \DateTimeInterface */
        $datetimeParam = $query->getParam($paramName, $defaultValue, type: 'datetime');

        // Then
        $this->assertEquals($defaultValue, $datetimeParam->format('Y-m-d'));
    }

    public function testGetUnsetDatetimeWithDatetimeDefault(): void
    {
        // Given
        $paramName = 'unsetDatetimeParam';
        $defaultDate = '2021-01-01';
        $defaultValue = new \DateTime($defaultDate);
        $query = new HTTPRequestQuery([]);

        // When
        /** @var \DateTimeInterface */
        $datetimeParam = $query->getParam($paramName, $defaultValue, type: 'datetime');

        // Then
        $this->assertEquals($defaultDate, $datetimeParam->format('Y-m-d'));
    }

    public function testDefaultDateIsIllegalString(): void
    {
        // Given
        $paramName = 'datetimeParam';
        $paramValue = '2021-01-01';
        $defaultValue = 'default';
        $query = new HTTPRequestQuery([$paramName => $paramValue]);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $query->getParam($paramName, $defaultValue, type: 'datetime');
    }

    // ============================
    // Test cases for isset
    // ============================

    public function testIsSet(): void
    {
        // Given
        $paramName = 'issetParam';
        $query = new HTTPRequestQuery([$paramName => 'value']);

        // When
        $isSet = $query->isSet($paramName);
        $notSet = $query->isSet('notSet');

        // Then
        $this->assertTrue($isSet);
        $this->assertFalse($notSet);
    }
}
