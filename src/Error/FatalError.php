<?php
declare(strict_types=1);

namespace UnionTypes\Error;

use Error;
use UnionTypes\Utilities\StackTrace;

class FatalError extends Error
{
    /**
     * Fatal Error
     *
     * @param string $message The fatal error message.
     * @param int $stackTraceIndex The stack trace index, by default `0`.
     */
    public function __construct(string $message, int $stackTraceIndex = 0)
    {
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $stackTraceIndex + 1);
        $calledInString = StackTrace::calledIn($backTrace, $stackTraceIndex);

        $message .= ", $calledInString";

        parent::__construct($message);
    }
}
