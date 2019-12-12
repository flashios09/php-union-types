<?php
declare(strict_types=1);

namespace UnionTypes\Error;

use TypeError as PhpTypeError;
use UnionTypes\Utilities\StackTrace;

class TypeError extends PhpTypeError
{
    /**
     * Type Error
     *
     * @param string $message The type error message, e.g `Argument 2 passed to Math::add(..., int|float $b) must be of
     * the union type int|float, string given`.
     * @param int $stackTraceIndex The stack trace index, `0` by default.
     */
    public function __construct(string $message, int $stackTraceIndex = 0)
    {
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $stackTraceIndex + 1);
        $calledInString = StackTrace::calledIn($backTrace, $stackTraceIndex);

        $message .= ", $calledInString";

        parent::__construct($message);
    }
}
