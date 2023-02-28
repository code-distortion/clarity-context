<?php

namespace CodeDistortion\ClarityContext\Tests\TestSupport;

/**
 * Helper methods for working with PHP stack traces.
 */
class PHPStackTraceHelper
{
    /**
     * Build a PHP stack trace at the point of the call.
     *
     * @return array<array<string,mixed>>
     */
    public static function buildPHPStackTraceHere(): array
    {
        return debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
    }

    /**
     * Work out what the current stack trace frame index is.
     *
     * @return integer
     */
    public static function getCurrentFrameIndex(): int
    {
        $phpStackTrace = PHPStackTraceHelper::buildPHPStackTraceHere();
        array_pop($phpStackTrace);
        return max(array_keys($phpStackTrace));
    }
}
