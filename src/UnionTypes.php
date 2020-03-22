<?php
declare(strict_types=1);

namespace UnionTypes;

use ReflectionFunction;
use ReflectionMethod;
use UnionTypes\Error\FatalError;
use UnionTypes\Error\TypeError;
use UnionTypes\Exception\ClassNotFoundException;
use UnionTypes\Exception\InvalidUnionTypeException;
use UnionTypes\Utilities\StackTrace;

class UnionTypes
{
    /**
     * A **pattern** used for classname type, it can be replaced by any valid classname, e.g. `Cake\ORM\Table`.
     *
     * @var string
     */
    public const CLASSNAME_PATTERN = '{Namespace}\{ClassName}';

    /**
     * The list of **valid** php types, without the `classname pattern type`.
     *
     * ### Notes
     * - We use the **strict** mode, `int` not `integer`, `bool` not `boolean` ...
     * - A passed `callable` value(e.g `function () {...}`) will be converted to `Closure` type which is an instance of
     * `object`.
     *
     * ### TODO
     * - Maybe adding **array of** types like on typescript `string[], int[], float[], bool[], array[]` ...
     *
     * @var string[]
     */
    public const UNION_TYPES = ['int', 'float', 'string', 'bool', 'array', 'null', 'resource'];

    /**
     * Get the full list of **valid** union types, including the `classname pattern type`.
     *
     * @return string[] The full list of **valid** union types, including the `classname pattern type`.
     */
    public static function FULL_UNION_TYPES(): array // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return array_merge(self::UNION_TYPES, [self::CLASSNAME_PATTERN]);
    }

    /**
     * Throw a **TypeError** if the given value isn't in the passed union type.
     *
     * @param mixed $value The value to check.
     * @param array $types The types to check against.
     * @return void
     * @throws \UnionTypes\Exception\ClassNotFoundException If the class of the passed type as a classname string
     * doesn't exist.
     * @throws \UnionTypes\Exception\InvalidUnionTypeException If the passed type isn't in the `self::UNION_TYPES` list
     * or isn't a classname string.
     * @throws \UnionTypes\Error\TypeError If the passed value isn't of the passed union type.
     */
    public static function assert($value, array $types): void
    {
        self::assertTypes(...$types);

        $type = self::getType($value);
        if (!in_array($type, $types)) {
            throw new TypeError(sprintf(
                "`%s` must be of the union type `%s`, `%s` given",
                self::stringify($value),
                implode('|', $types),
                $type
            ));
        }
    }

    /**
     * Check if the value type is one of the passed types.
     *
     * ### Examples
     * ```php
     * // equivalent to `is_int(1.2) || is_float(1.2)`
     * UnionTypes::is(1.2, ['int', 'float']); // true
     * // equivalent to `is_int('1.2') || is_float('1.2')`
     * UnionTypes::is('1.2', ['int', 'float']); // false
     * // equivalent to `is_int('1.2') || is_float('1.2') || is_string('1.2')`
     * UnionTypes::is('1.2', ['int', 'float', 'string']); // true
     * ```
     *
     * @param mixed $value The value to check, e.g. `1.2`.
     * @param array $types The union types to check against, e.g. `['int', 'float']`.
     * @return bool `true` if is value type is one the passed types, `false` otherwise.
     * @throws \UnionTypes\Exception\ClassNotFoundException If the class of the passed type as a classname string
     * doesn't exist.
     * @throws \UnionTypes\Exception\InvalidUnionTypeException If the passed type isn't in the `self::UNION_TYPES` list
     * or isn't a classname string.
     */
    public static function is($value, array $types): bool
    {
        self::assertTypes(...$types);

        $type = self::getType($value);
        $is = in_array($type, $types);

        return $is;
    }

    /**
     * Throw a **TypesError** if the value of the argument isn't in the union type.
     *
     * ### Valid union types list
     * - `'string'`
     * - `'int'`(not `'integer'` or `'double'`)
     * - `'float'`(not `'double'` or `'decimal'`)
     * - `'bool'`(not `'boolean'`)
     * - `'array'`
     * - `'null'`(not `'NULL'`)
     * - Any valid **classname string**, e.g `Table::class` or `'Cake\ORM\Table'`
     *
     * @param string $argName The arg name, e.g. `data`.
     * @param array $types The types array, e.g. `['string', 'array']`.
     * @return void
     * @throws \UnionTypes\Error\FatalError If it is not invoked **INSIDE** a function/method or the function itself
     * doesn't accept any argument or invalid argument name is used.
     * @throws \UnionTypes\Error\TypeError If the argument value isn't of the passed union type.
     */
    public static function assertFuncArg(string $argName, array $types): void
    {
        self::assertTypes(...$types);

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $assertFuncArgStackTrace = $backtrace[0];

        if (!array_key_exists(1, $backtrace)) {
            throw new FatalError(sprintf(
                'The `%s` must be invoked **INSIDE** a function or method',
                StackTrace::theFunc($assertFuncArgStackTrace)
            ));
        }

        // get the func args using Reflexion class
        $calleeStackTrace = $backtrace[1];
        $reflectionFunc = isset($calleeStackTrace['class']) ?
            new ReflectionMethod($calleeStackTrace['class'], $calleeStackTrace['function']) :
            new ReflectionFunction($calleeStackTrace['function']);

        if ($reflectionFunc->getNumberOfParameters() === 0) {
            throw new FatalError(sprintf(
                "The `%s` %s doesn't accept any argument",
                StackTrace::theFunc($calleeStackTrace),
                isset($calleeStackTrace['class']) ? 'method' : 'function'
            ), 1);
        }

        $reflectionFuncParameters = $reflectionFunc->getParameters();
        $funcArgs = array_map(function ($value) {
            return $value->name;
        }, $reflectionFuncParameters);

        if (!in_array($argName, $funcArgs)) {
            $callback = function ($value) {
                return "'" . $value . "'";
            };

            throw new FatalError(sprintf(
                "Invalid argument name `%s` for `%s`, try one of those values `%s`",
                $argName,
                StackTrace::theFunc($calleeStackTrace),
                implode(', ', array_map($callback, $funcArgs))
            ));
        }

        // now get the func arg value and assert it
        $argIndex = array_search($argName, $funcArgs);
        $argOffset = $argIndex + 1;
        // check if the arg value has been defined or use the default one
        $argValue = self::_getArgValue($argIndex, $calleeStackTrace['args'], $reflectionFuncParameters);

        $argType = self::getType($argValue);
        if (!in_array($argType, $types)) {
            throw new TypeError(sprintf(
                "Argument `%s` passed to `%s(%s)` must be of the union type `%s`, `%s` given",
                $argOffset,
                StackTrace::theFunc($calleeStackTrace, ['parentheses' => false]),
                StackTrace::funcArgsEllipsis($argName, $funcArgs, ...$types),
                implode('|', $types),
                $argType
            ), 1);
        }
    }

    /**
     * Assert the union types.
     *
     * ### Valid union types list
     * - `'string'`
     * - `'int'`(not `'integer'` or `'double'`)
     * - `'float'`(not `'double'` or `'decimal'`)
     * - `'bool'`(not `'boolean'`)
     * - `'array'`
     * - `'null'`(not `'NULL'`)
     * - Any valid **classname string**, e.g `Table::class` or `'Cake\ORM\Table'`
     * - `resource`
     *
     * @param string ...$types The types to assert.
     * @return void
     * @throws \UnionTypes\Exception\ClassNotFoundException If the class of the passed type as a classname string
     * doesn't exist.
     * @throws \UnionTypes\Exception\InvalidUnionTypeException If the passed type isn't in the `self::UNION_TYPES` list
     * or isn't a classname string.
     */
    public static function assertTypes(string ...$types): void
    {
        foreach ($types as $type) {
            if ($type === 'stdClass') {
                continue;
            }

            // `'NULL'` must be before the classname regex, otherwise it will be treated as a classname
            if ($type === 'NULL') {
                throw new InvalidUnionTypeException($type, 'null');
            }

            // classname, e.g. `Cake\ORM\Table`
            if (preg_match('/^[A-Z][_A-Za-z0-9]*(?:[\\\][A-Z][_A-Za-z0-9]*)*$/', $type) === 1) {
                if (!class_exists($type)) {
                    throw new ClassNotFoundException($type);
                }

                continue;
            }

            if ($type === 'integer' || $type === 'double') {
                throw new InvalidUnionTypeException($type, 'int');
            }

            if ($type === 'decimal') {
                throw new InvalidUnionTypeException($type, 'float');
            }

            if ($type === 'boolean') {
                throw new InvalidUnionTypeException($type, 'bool');
            }

            if ($type === 'object') {
                throw new InvalidUnionTypeException(
                    $type,
                    sprintf("{ClassName}::class or '%s' format", self::CLASSNAME_PATTERN)
                );
            }

            if (!in_array($type, self::UNION_TYPES)) {
                throw new InvalidUnionTypeException($type, ...self::FULL_UNION_TYPES());
            }
        }
    }

    /**
     * Get the type of a given value.
     *
     * ### Possible return type
     * - `'int'` for **integer**
     * - `'float'` for **float**
     * - `'string'` for **string**
     * - `'bool'` for **boolean**
     * - `'array'` for **array**
     * - `'null'` for **null**
     * - The **classname** for **object**, e.g. for `UnionTypes::getType(new Table())` returns `'Cake\ORM\Table'`
     * - `'resource'` for **resource**, e.g. for `UnionTypes::getType(fopen('my-file.txt', 'r'))` returns `'resource'`
     * - `'Unkown type'` for **any other type value**
     *
     * @param mixed $value The value, e.g `'my string'`.
     * @return string The type of the value, e.g. `'string'`.
     */
    public static function getType($value): string
    {
        if (is_int($value)) {
            return 'int';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_bool($value)) {
            return 'bool';
        }

        if (is_array($value)) {
            return 'array';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_object($value)) {
            return get_class($value);
        }

        if (is_resource($value)) {
            return 'resource';
        }

        return "Unknown type";
    }

    /**
     * Convert any value to string.
     *
     * ### Return examples
     * - (string)`1` for (int)`1`
     * - (string)`0` for (int)`0`
     * - (string)`1.2` for (float)`1.2`
     * - (string)`'my string'` for (string)`my string`, wrapped with **quotes** !!
     * - (string)`null` for (NULL)`null`
     * - (string)`true` for (bool)`true`
     * - (string)`false` for (bool)`false`
     * - (string)`Array` for any **array**
     * - (string)`object({ClassName})` for any **object**, e.g. for `UnionTypes::stringify(new Table())` returns
     * `'object(Cake\ORM\Table)'`
     * - (string)`Resource id #{n}` for any **resource**, e.g. for `UnionTypes::stringify(fopen('file.txt', 'r'))`
     * returns `Resource id #2`
     * - (string)`Unkown value type` for **any other value type**
     *
     * @param mixed $value The value.
     * @return string The converted to string value.
     */
    public static function stringify($value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        if ($value === null) {
            return 'null';
        }

        if (is_string($value)) {
            return "'" . $value . "'";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return 'Array';
        }

        if (is_object($value)) {
            return 'object(' . get_class($value) . ')';
        }

        if (is_resource($value)) {
            return (string)$value;
        }

        return "Unknown value type";
    }

    /**
     * Check if the arg value has been passed or use the arg default value.
     *
     * ### Examples
     * The `$calleeStackTraceArgs` doesn't have the wanted arg because it is **optional** hasn't been passed, so we
     * have to get it from the `ReflectionFunction/ReflectionClass` using `ReflectionParameter::getDefaultValue`.
     * ```php
     * public function setAndRender(array $viewVars, $template = null, $layout = null);
     * // invocation
     * $controller->setAndRender(['posts' => $posts]);
     * // inside `setAndRender`
     * UnionTypes::assertFuncArg('template', ['string', 'array', 'null']);
     *
     * // to
     * $argIndex = 1; // template
     * $calleeStackTraceArgs = [
     *     0 => 'viewVars'
     * ];
     * $reflectionFuncParameters = [
     *     0 => \ReflectionParameter(['name' => 'viewVars'])
     *     1 => \ReflectionParameter(['name' => 'template', 'getDefaultValue()' => null])
     *     2 => \ReflectionParameter(['name' => 'layout', 'getDefaultValue()' => null])
     * ];
     * $argValue = self::_getArgValue($argIndex, $calleeStackTraceArgs, $reflectionFuncParameters); // null
     * ```
     *
     * @param int $argIndex The argument index, e.g. `0`, `1` ...
     * @param array $calleeStackTraceArgs The callee statck trace args array.
     * @param \ReflectionParameter[] $reflectionFuncParameters The reflection func parameters array.
     * @return mixed Depending on the arg value.
     */
    protected static function _getArgValue(int $argIndex, array $calleeStackTraceArgs, array $reflectionFuncParameters)
    {
        $argValue = array_key_exists($argIndex, $calleeStackTraceArgs)
            ? $calleeStackTraceArgs[$argIndex]
            : $reflectionFuncParameters[$argIndex]->getDefaultValue();

        return $argValue;
    }
}
