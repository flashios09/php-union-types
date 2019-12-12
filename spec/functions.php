<?php
declare(strict_types=1);

namespace UnionTypes\Spec;

use Exception;

/**
 * Get a specific trace array using a trace name.
 *
 * Used to monkey patch the `debug_backtrace` function.
 *
 * @param string $traceName The trace name, e.g. `UnionTypes::assertTypes`.
 * @param array $overwrite Overwrite some/all trace name array keys/values.
 * @return array The trace array.
 * @throws \Exception If the trace name not found.
 */
function getTrace(string $traceName, array $overwrite = []): array
{
    $backTrace = [
        'Math::add' => [
            "file" => "spec/Fixtures/Math.php",
            "line" => 33,
            "function" => 'add',
            "class" => 'UnionTypes\Spec\Fixtures\Math',
            "type" => "::",
        ],
        'Math::random' => [
            "file" => "spec/Fixtures/Math.php",
            "line" => 16,
            "function" => 'random',
            "class" => 'UnionTypes\Spec\Fixtures\Math',
            "type" => "::",
        ],
        'UnionTypes::assertTypes' => [
            "file" => "src/UnionTypes.php",
            "line" => 206,
            "function" => 'assertTypes',
            "class" => 'UnionTypes\UnionTypes',
            "type" => "::",
        ],
        'UnionTypes::assert' => [
            "file" => "src/UnionTypes.php",
            "line" => 60,
            "function" => 'assert',
            "class" => 'UnionTypes\UnionTypes',
            "type" => "::",
        ],
        'UnionTypes::assertFuncArg' => [
            "file" => "src/UnionTypes.php",
            "line" => 125,
            "function" => 'assertFuncArg',
            "class" => 'UnionTypes\UnionTypes',
            "type" => "::",
        ],
    ];

    if (array_key_exists($traceName, $backTrace)) {
        return array_merge($overwrite, $backTrace[$traceName]);
    }

    throw new Exception("Undefined trace name `$traceName`");
}
