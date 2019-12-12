<?php
declare(strict_types=1);

namespace UnionTypes\Exception;

use Exception;
use UnionTypes\Utilities\StackTrace;

class ClassNotFoundException extends Exception
{
    /**
     * If the class of the passed type as a classname string doesn't exist.
     *
     * @param string $className The classname, e.g. `Cake\ORM\Tablr`.
     */
    public function __construct(string $className)
    {
        // the **REAL** stack trace of the error is the `#1` not `#0` index !!!
        $stackTraceIndex = 1;
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $stackTraceIndex + 1);
        $calledInString = StackTrace::calledIn($backTrace, $stackTraceIndex);

        $message = "Class `$className` not found, $calledInString";

        parent::__construct($message);
    }
}
