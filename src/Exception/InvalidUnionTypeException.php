<?php
declare(strict_types=1);

namespace UnionTypes\Exception;

use Exception;
use UnionTypes\Utilities\StackTrace;

class InvalidUnionTypeException extends Exception
{
    /**
     * If the passed type isn't in the `UnionTypes::UNION_TYPES` list or isn't a classname string.
     *
     * @param string $invalidType The invalid type, e.g `'integer'`, `'boolean'`, `'object'` ...
     * @param string $use The types list to use instead, e.g `'int'`, `'bool'`, ...
     */
    public function __construct(string $invalidType, string ...$use)
    {
        $callback = function ($value) {
            return "'" . $value . "'";
        };

        $useLength = count($use);
        $useString = $useLength > 1 ? implode(', ', array_map($callback, $use)) : $use[0];

        $stackTraceIndex = 0;
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $stackTraceIndex + 1);
        $calledInString = StackTrace::calledIn($backTrace, $stackTraceIndex);

        $message = sprintf(
            "Invalid union type `%s`, use %s`%s` instead, %s",
            $invalidType,
            $useLength > 1 ? "one of those types " : null,
            $useString,
            $calledInString
        );

        parent::__construct($message);
    }
}
