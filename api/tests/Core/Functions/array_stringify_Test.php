<?php

// Path: api/tests/Core/Functions/array_stringify_Test.php

declare(strict_types=1);

namespace App\Tests\Core\Functions;

include_once API . "/src/Core/Functions/array_stringify.php";

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function App\Core\Functions\array_stringify;

#[CoversFunction('App\Core\Functions\array_stringify')]
final class array_stringify_Test extends TestCase
{
    public function test_array_stringify(): void
    {
        // Given
        $array = [
            "key1" => "value1",
            "key2" => "value2",
            "key3" => [
                "key3.1" => "value3.1",
                "key3.2" => "value3.2",
                "key3.3" => [
                    "key3.3.1" => "value3.3.1",
                    "key3.3.2" => "value3.3.2",
                    "key3.3.3" => new class {
                        public string $key3_3_3_1 = "value3.3.3.1";
                        public string $key3_3_3_2 = "value3.3.3.2";
                    },
                ],
            ],
        ];

        $expected = <<<EOT
key1 => value1
key2 => value2
key3 => [
  key3.1 => value3.1
  key3.2 => value3.2
  key3.3 => [
    key3.3.1 => value3.3.1
    key3.3.2 => value3.3.2
    key3.3.3 => class@anonymous Object
(
    [key3_3_3_1] => value3.3.3.1
    [key3_3_3_2] => value3.3.3.2
)

  ]
]

EOT;

        // When
        $actual = array_stringify($array);

        // Then
        $this->assertEquals($expected, $actual);
    }
}
