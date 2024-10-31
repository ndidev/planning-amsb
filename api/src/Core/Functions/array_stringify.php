<?php

namespace App\Core\Functions;

/**
 * Stringify an array.
 * 
 * @param array<mixed> $array       Array to be stringified.
 * @param int          $indentation Indentation spaces for the string output.
 * @param int          $depth       Current depth of the array.
 * @param int          $maxDepth    Maximum depth of the array to be stringified.
 * 
 * @return string Stringified array
 */
function array_stringify(
    array $array,
    int $indentation = 0,
    int $depth = 0,
    int $maxDepth = 5,
): string {
    $string = "";
    $indentation_spaces = str_repeat(" ", $indentation);
    $maxDepthIsReached = $depth >= $maxDepth;

    foreach ($array as $key => $value) {
        // If $value is an array, recursive stringification
        if (gettype($value) === "array" && !$maxDepthIsReached) {
            $value = "["
                . PHP_EOL
                . array_stringify($value, $indentation + 2, $depth + 1, $maxDepth)
                . str_repeat(" ", $indentation)
                . "]";
        }

        if (is_object($value)) {
            $value = print_r($value, true);
        }

        $string .= $indentation_spaces . "$key => $value" . PHP_EOL;
    }

    return $string;
}
