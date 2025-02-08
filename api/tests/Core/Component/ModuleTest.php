<?php

// Path: api/tests/Core/Component/ModuleTest.php

declare(strict_types=1);

namespace App\Tests\Core\Component;

use App\Core\Component\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Module::class)]
final class ModuleTest extends TestCase
{
    #[DataProvider('generateDataProvider')]
    public function testTryFromRetursExpectedResults(
        ?string $temptativeModuleName,
        ?string $expected
    ): void {
        // When
        $actual = Module::tryFrom($temptativeModuleName);

        // Then
        $this->assertSame($expected, $actual);
    }

    /**
     * @return \Generator<array{?string, ?string}>
     */
    public static function generateDataProvider(): \Generator
    {
        yield 'null' => [null, null];
        yield 'empty string' => ['', null];
        yield 'unknown module name' => ['unknown', null];
        yield 'bulk' => ['vrac', Module::BULK];
        yield 'chartering' => ['chartering', Module::CHARTERING];
        yield 'config' => ['config', Module::CONFIG];
        yield 'shipping' => ['consignation', Module::SHIPPING];
        yield 'timber' => ['bois', Module::TIMBER];
        yield 'third party' => ['tiers', Module::THIRD_PARTY];
        yield 'user' => ['user', Module::USER];
    }
}
