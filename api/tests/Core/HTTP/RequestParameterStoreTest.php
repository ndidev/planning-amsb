<?php

// Path: api/tests/Core/HTTP/RequestParameterStoreTest.php

namespace App\Tests\Core\HTTP;

use App\Core\HTTP\RequestParameterStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type QueryArray from \App\Core\HTTP\RequestParameterStore
 */
#[CoversClass(RequestParameterStore::class)]
final class RequestParameterStoreTest extends TestCase
{
    /**
     * @phpstan-param QueryArray $parameterBag
     */
    private function makeRequestParameterStore(array $parameterBag): RequestParameterStore
    {
        $parameterStore = new class($parameterBag) extends RequestParameterStore {
            public function __construct(array $parameterBag)
            {
                parent::__construct($parameterBag);
            }
        };

        return $parameterStore;
    }

    // ============================
    // Test cases for string params
    // ============================

    public function testGetSetString(): void
    {
        // Given
        $paramName = 'stringParam';
        $paramValue = 'stringValue';
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);


        // When
        $stringParam = $parameterStore->getString($paramName);

        // Then
        $this->assertSame($paramValue, $stringParam);
    }

    public function testGetDefaultWithEmptyString(): void
    {
        // Given
        $paramName = 'emptyStringParam';
        $defaultValue = 'default';
        $parameters = [$paramName => ''];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $stringParam = $parameterStore->getString($paramName, $defaultValue, false);

        // Then
        $this->assertSame($defaultValue, $stringParam);
    }

    public function testGetAllowedEmptyString(): void
    {
        // Given
        $paramName = 'emptyStringParam';
        $paramValue = '';
        $defaultValue = 'default';
        $parameters = [$paramName => ''];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $stringParam = $parameterStore->getString($paramName, $defaultValue, true);

        // Then
        $this->assertSame($paramValue, $stringParam);
    }

    public function testGetUnsetString(): void
    {
        // Given
        $paramName = 'unsetStringParam';
        $defaultValue = 'default';
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $stringParam = $parameterStore->getString($paramName, $defaultValue);

        // Then
        $this->assertSame($defaultValue, $stringParam);
    }

    public function testGetUnsetStringWithEmptyStringDefault(): void
    {
        // Given
        $paramName = 'unsetStringParam';
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $stringParam = $parameterStore->getString($paramName);

        // Then
        $this->assertSame('', $stringParam);
    }

    // ============================
    // Test cases for integer params
    // ============================

    public function testGetSetInteger(): void
    {
        // Given
        $paramName = 'integerParam';
        $paramValue = '42';
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $integerParam = $parameterStore->getInt($paramName);

        // Then
        $this->assertSame(42, $integerParam);
    }

    public function testGetDefaultWithEmptyInteger(): void
    {
        // Given
        $paramName = 'emptyIntegerParam';
        $paramValue = '';
        $defaultValue = 42;
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $integerParam = $parameterStore->getInt($paramName, $defaultValue);

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
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $integerParam = $parameterStore->getInt($paramName, $defaultValue, true);

        // Then
        $this->assertSame($expectedValue, $integerParam);
    }

    public function testGetUnsetInteger(): void
    {
        // Given
        $paramName = 'unsetIntegerParam';
        $defaultValue = 42;
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $integerParam = $parameterStore->getInt($paramName, $defaultValue);

        // Then
        $this->assertSame($defaultValue, $integerParam);
    }

    public function testGetUnsetIntegerWithNullDefault(): void
    {
        // Given
        $paramName = 'unsetIntegerParam';
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $integerParam = $parameterStore->getInt($paramName);

        // Then
        $this->assertNull($integerParam);
    }

    // ============================
    // Test cases for float params
    // ============================

    public function testGetSetFloat(): void
    {
        // Given
        $paramName = 'floatParam';
        $paramValue = 42.42;
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $floatParam = $parameterStore->getFloat($paramName);

        // Then
        $this->assertSame($paramValue, $floatParam);
    }

    public function testGetDefaultWithEmptyFloat(): void
    {
        // Given
        $paramName = 'emptyFloatParam';
        $paramValue = '';
        $defaultValue = 42.42;
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $floatParam = $parameterStore->getFloat($paramName, $defaultValue);

        // Then
        $this->assertSame($defaultValue, $floatParam);
    }

    public function testGetAllowedEmptyFloat(): void
    {
        // Given
        $paramName = 'emptyFloatParam';
        $paramValue = '';
        $defaultValue = 42.42;
        $expectedValue = 0.0;
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $floatParam = $parameterStore->getFloat($paramName, $defaultValue, true);

        // Then
        $this->assertSame($expectedValue, $floatParam);
    }

    public function testGetUnsetFloat(): void
    {
        // Given
        $paramName = 'unsetFloatParam';
        $defaultValue = 42.42;
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $floatParam = $parameterStore->getFloat($paramName, $defaultValue);

        // Then
        $this->assertSame($defaultValue, $floatParam);
    }

    public function testGetUnsetFloatWithNullDefault(): void
    {
        // Given
        $paramName = 'unsetFloatParam';
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $floatParam = $parameterStore->getFloat($paramName);

        // Then
        $this->assertNull($floatParam);
    }

    // ============================
    // Test cases for boolean params
    // ============================

    public function testGetSetBoolean(): void
    {
        // Given
        $paramName = 'booleanParam';
        $paramValue = 'true';
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $booleanParam = $parameterStore->getBool($paramName);

        // Then
        $this->assertTrue($booleanParam);
    }

    public function testGetDefaultWithEmptyBoolean(): void
    {
        // Given
        $paramName = 'emptyBooleanParam';
        $paramValue = '';
        $defaultValue = true;
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $booleanParam = $parameterStore->getBool($paramName, $defaultValue);

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
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $booleanParam = $parameterStore->getBool($paramName, $defaultValue, true);

        // Then
        $this->assertSame($expectedValue, $booleanParam);
    }

    public function testGetUnsetBoolean(): void
    {
        // Given
        $paramName = 'unsetBooleanParam';
        $defaultValue = true;
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $booleanParam = $parameterStore->getBool($paramName, $defaultValue);

        // Then
        $this->assertTrue($booleanParam);
    }

    public function testGetUnsetBooleanWithFalseDefault(): void
    {
        // Given
        $paramName = 'unsetBooleanParam';
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $booleanParam = $parameterStore->getBool($paramName);

        // Then
        $this->assertFalse($booleanParam);
    }

    public function testGetUnsetBooleanWithNullDefault(): void
    {
        // Given
        $paramName = 'unsetBooleanParam';
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $booleanParam = $parameterStore->getBool($paramName, null);

        // Then
        $this->assertNull($booleanParam);
    }

    // ============================
    // Test cases for array params
    // ============================

    public function testGetSetArray(): void
    {
        // Given
        $paramName = 'arrayParam';
        $paramValue = ['value1', 'value2'];
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $arrayParam = $parameterStore->getArray($paramName);

        // Then
        $this->assertSame($paramValue, $arrayParam);
    }

    public function testGetDefaultWithEmptyArray(): void
    {
        // Given
        $paramName = 'emptyArrayParam';
        $paramValue = [];
        $defaultValue = ['default'];
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $arrayParam = $parameterStore->getArray($paramName, $defaultValue);

        // Then
        $this->assertSame($defaultValue, $arrayParam);
    }

    public function testGetAllowedEmptyArray(): void
    {
        // Given
        $paramName = 'emptyArrayParam';
        $paramValue = [];
        $defaultValue = ['default'];
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $arrayParam = $parameterStore->getArray($paramName, $defaultValue, true);

        // Then
        $this->assertSame($paramValue, $arrayParam);
    }

    public function testGetUnsetArray(): void
    {
        // Given
        $paramName = 'unsetArrayParam';
        $defaultValue = ['default'];
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $arrayParam = $parameterStore->getArray($paramName, $defaultValue);

        // Then
        $this->assertSame($defaultValue, $arrayParam);
    }

    public function testGetUnsetArrayWithEmptyDefault(): void
    {
        // Given
        $paramName = 'unsetArrayParam';
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $arrayParam = $parameterStore->getArray($paramName);

        // Then
        $this->assertEmpty($arrayParam);
    }

    // ============================
    // Test cases for datetime params
    // ============================

    public function testGetSetDatetime(): void
    {
        // Given
        $paramName = 'datetimeParam';
        $paramValue = '2021-01-01';
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $datetimeParam = $parameterStore->getDatetime($paramName);

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
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        /** @var \DateTimeInterface */
        $datetimeParam = $parameterStore->getDatetime($paramName, $defaultValue);

        // Then
        $this->assertEquals($defaultDate, $datetimeParam->format('Y-m-d'));
    }

    public function testGetAllowedEmptyDatetime(): void
    {
        // Given
        $paramName = 'emptyDatetimeParam';
        $paramValue = '';
        $defaultValue = new \DateTime('2021-01-01');
        $expectedDate = (new \DateTime())->format('Y-m-d');
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        /** @var \DateTimeInterface */
        $datetimeParam = $parameterStore->getDatetime($paramName, $defaultValue, true);

        // Then
        $this->assertEquals($expectedDate, $datetimeParam->format('Y-m-d'));
    }

    public function testGetUnsetDatetimeWithStringDefault(): void
    {
        // Given
        $paramName = 'unsetDatetimeParam';
        $defaultValue = '2021-01-01';
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        /** @var \DateTimeInterface */
        $datetimeParam = $parameterStore->getDatetime($paramName, $defaultValue);

        // Then
        $this->assertEquals($defaultValue, $datetimeParam->format('Y-m-d'));
    }

    public function testGetUnsetDatetimeWithDatetimeDefault(): void
    {
        // Given
        $paramName = 'unsetDatetimeParam';
        $defaultDate = '2021-01-01';
        $defaultValue = new \DateTime($defaultDate);
        $parameters = [];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        /** @var \DateTimeInterface */
        $datetimeParam = $parameterStore->getDatetime($paramName, $defaultValue);

        // Then
        $this->assertEquals($defaultDate, $datetimeParam->format('Y-m-d'));
    }

    public function testDefaultDatetimeIsIllegalString(): void
    {
        // Given
        $paramName = 'datetimeParam';
        $paramValue = '2021-01-01';
        $defaultValue = 'default';
        $parameters = [$paramName => $paramValue];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $parameterStore->getDatetime($paramName, $defaultValue);
    }

    // ============================
    // Test cases for wrong types
    // ============================

    #[DataProvider('generateTypes')]
    public function testDefaultValueHasWrongType(
        mixed $defaultValue,
        string $expectedType
    ): void {
        // Given
        $paramName = 'paramName';
        $parameters = [$paramName => null];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // Then
        $this->expectException(\InvalidArgumentException::class);
        $parameterStore->getParam($paramName, $defaultValue, $expectedType);
    }

    /**
     * @return \Generator<array{mixed, string}>
     */
    public static function generateTypes(): \Generator
    {
        // String
        yield [1, 'string'];
        yield [1.0, 'string'];
        yield [true, 'string'];
        yield [[], 'string'];
        yield [new \DateTime(), 'string'];

        // Integer
        yield ['string', 'int'];
        yield [1.0, 'int'];
        yield [true, 'int'];
        yield [[], 'int'];
        yield [new \DateTime(), 'int'];

        // Float
        yield ['string', 'float'];
        yield [1, 'float'];
        yield [true, 'float'];
        yield [[], 'float'];
        yield [new \DateTime(), 'float'];

        // Boolean
        yield ['string', 'bool'];
        yield [1, 'bool'];
        yield [1.0, 'bool'];
        yield [[], 'bool'];
        yield [new \DateTime(), 'bool'];

        // Array
        yield ['string', 'array'];
        yield [1, 'array'];
        yield [1.0, 'array'];
        yield [true, 'array'];
        yield [new \DateTime(), 'array'];

        // Datetime
        yield ['string', 'datetime'];
        yield [1, 'datetime'];
        yield [1.0, 'datetime'];
        yield [true, 'datetime'];
        yield [[], 'datetime'];
    }

    // ============================
    // Test cases for isset
    // ============================

    public function testIsSet(): void
    {
        // Given
        $setParamName = 'setParam';
        $notSetParamName = 'notSetParam';
        $nullParamName = 'nullParam';
        $parameters = [
            $setParamName => '',
            $nullParamName => null,
        ];
        $parameterStore = $this->makeRequestParameterStore($parameters);

        // When
        $isSet = $parameterStore->isSet($setParamName);
        $notSet = $parameterStore->isSet($notSetParamName);
        $nullParam = $parameterStore->isSet($nullParamName);

        // Then
        $this->assertTrue($isSet);
        $this->assertFalse($notSet);
        $this->assertFalse($nullParam);
    }
}
