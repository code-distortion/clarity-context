<?php

namespace CodeDistortion\ClarityContext\Tests\TestSupport;

use CodeDistortion\ClarityContext\Clarity;
use CodeDistortion\ClarityContext\Support\CallStack\CallStack;
use Exception;

/**
 * A class with methods called by tests, simply because it's code running in a different class.
 *
 * i.e. when the frame the call is in is important.
 */
class SomeOtherClass
{
    /**
     * Add context to Clarity, and check the meta that's recorded.
     *
     * @param string|null $context The context to store (will skip when empty).
     * @return CallStack
     */
    public function addContextAndCheckMeta(?string $context): CallStack
    {
        if ($context) {
            Clarity::context($context);
        }

        return Clarity::buildContextHere()->getCallStack();
    }

    /**
     * Add context to Clarity, and check the meta that's recorded.
     *
     * @param string|null $context The context to store (will skip when empty).
     * @return CallStack
     */
    public static function addContextAndCheckMetaStatic(?string $context): CallStack
    {
        if ($context) {
            Clarity::context($context);
        }

        return Clarity::buildContextHere()->getCallStack();
    }

    /**
     * Generate an exception and return it.
     *
     * @return Exception
     */
    public static function generateException(): Exception
    {
        return new Exception();
    }
}
