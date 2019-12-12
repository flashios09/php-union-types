<?php
declare(strict_types=1);

namespace UnionTypes\Utilities;

class StackTrace
{
    /**
     * Return a filename relative to workspace, e.g. `src/Table/PostsTable.php`.
     *
     * A friendly editor(SublimeText, Atom, VSCODE) format, without the `/path/to/app/` prefix.
     *
     * ### Notes
     * - **IMPURE** function !
     * - To remove the `/path/to/app/` prefix, you need to define somewhere the `UnionTypes.PATH_TO_APP` constant, e.g.:
     * ```php
     * // using `$_SERVER`(isn't available in **php cli**)
     * $PATH_TO_APP = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR : '';
     * define('UnionTypes.PATH_TO_APP', $PATH_TO_APP);
     * // using `dirname(__FILE__)`, maybe you need to remove/add some parts, depending on the `__FILE__` location.
     * define('UnionTypes.PATH_TO_APP', dirname(__FILE__) . DIRECTORY_SEPARATOR;
     * // using the `PWD` key of the `getEnv()` array
     * define('UnionTypes.PATH_TO_APP', getEnv()['PWD'] . DIRECTORY_SEPARATOR;
     * ```
     *
     * @param string $file The full file path, e.g. `/path/to/app/src/Table/PostsTable.php`.
     * @return string Filename relative to workspace, e.g `src/Table/PostsTable.php`
     * or `/path/to/app/src/Table/PostsTable.php` if `UnionTypes.PATH_TO_APP` isn't defined.
     */
    public static function fileRelativeToWorkspace(string $file): string
    {
        $PATH_TO_APP = defined('UnionTypes.PATH_TO_APP') ? constant('UnionTypes.PATH_TO_APP') : null;
        if (is_string($PATH_TO_APP)) {
            $file = (string)str_replace($PATH_TO_APP, '', $file);
        }

        return $file;
    }

    /**
     * Return theFunc format `'ClassName::funcName()'` using the passed stack trace, e.g. `'Math::add()'`.
     *
     * ### Notes
     * - **IMPURE** function: see `UnionTypes\Utilities\Stacktace::fileRelativeToWorkspace(string $file): string`
     *
     * ### Options
     * - `parentheses` - [Optional] (bool) Used to add `'()'` after the function name, `true` by default.
     *
     * @param array $stackTrace The stack trace array.
     * @param array $options The passes options.
     * @return string The func string, e.g. `'Math::add()'`, `'index.php:25::add()'`
     */
    public static function theFunc(array $stackTrace, array $options = []): string
    {
        $options += [
            'parentheses' => true,
        ];

        $theFunc = sprintf(
            '%s::%s%s',
            $stackTrace['class'] ?? sprintf(
                '%s:%d',
                self::fileRelativeToWorkspace($stackTrace['file']),
                $stackTrace['line']
            ),
            $stackTrace['function'],
            $options['parentheses'] ? '()' : ''
        );

        return $theFunc;
    }

    /**
     * Return the func args ellipsis string, e.g `'..., string|array $data'`.
     *
     * @param string $argName The arg name, e.g. `data`.
     * @param array $funcArgs The func args, e.g. `[0 => 'id', 1 => 'data']`
     * @param string ...$types The types list, e.g. `string`, `array` ...
     * @return string The func arg ellipsis string, e.g. `'..., string|array $data'`
     */
    public static function funcArgsEllipsis(string $argName, array $funcArgs, string ...$types): string
    {
        $theArg = sprintf('%s $%s', implode('|', (array)$types), $argName);
        $argsCount = count($funcArgs);

        // uniq arg
        if ($argsCount === 1) {
            return $theArg;
        }

        $argOffset = array_search($argName, $funcArgs) + 1;

        // first arg
        if ($argOffset === 1) {
            return "$theArg, ...";
        }

        // arg in the middle
        if ($argOffset > 0 && $argOffset < $argsCount) {
            return "..., $theArg, ...";
        }

        // last arg
        if ($argOffset === $argsCount) {
            return "..., $theArg";
        }
    }

    /**
     * Find the stack trace using the index.
     *
     * ### Return example
     * ```php
     * [
     *     'file' => '/path/to/app/src/Utility/UnionType.php',
     *     'line' => (int) 22,
     *     'function' => '_assertTypes',
     *     'class' => 'App\Utility\UnionType',
     *     'type' => '->',
     *     'args' => [
     *         (int) 0 => 'string',
     *         (int) 1 => 'array',
     *         (int) 2 => 'Cake\ORM\Tablr'
     *     ],
     *     // see `UnionTypes\Utilities\StackTrace::fileRelativeToWorkspace(string $file): string`
     *     'fileRelativeToWorkspace' => 'src/Utility/UnionType.php',
     *     // see `UnionTypes\Utilities\StackTrace::theFunc(array $stackTrace): string`
     *     'theFunc' => 'App\Utility\UnionType::_assertTypes()'
     * ]
     * ```
     *
     * @param array $fullStackTrace The full stack trace array.
     * @param int $index The stack trace index.
     * @return array|null The stack trace `array`, `null` if the stack trace index not found.
     */
    public static function findStackTraceByIndex(array $fullStackTrace, int $index): ?array
    {
        if (!array_key_exists($index, $fullStackTrace)) {
            return null;
        }

        $stackTrace = $fullStackTrace[$index];
        $theStackTrace = array_merge($stackTrace, [
            'fileRelativeToWorkspace' => self::fileRelativeToWorkspace($stackTrace['file']),
            'theFunc' => self::theFunc($stackTrace),
        ]);

        return $theStackTrace;
    }

    /**
     * Return the **called in** string.
     *
     * @param array $fullStackTrace The full stack trace array.
     * @param int $stackTraceIndex The stack trace index.
     * @return string The **called in** string, e.g. `'called in #0 src/Table/PostsTable:10'`.
     */
    public static function calledIn(array $fullStackTrace, int $stackTraceIndex): string
    {
        [
            'fileRelativeToWorkspace' => $fileRelativeToWorkspace,
            'line' => $line,
        ] = self::findStackTraceByIndex($fullStackTrace, $stackTraceIndex);

        $calledIn = "called in #$stackTraceIndex `$fileRelativeToWorkspace:$line`";

        return $calledIn;
    }
}
