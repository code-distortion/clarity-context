<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\InternalAPI;

use CodeDistortion\ClarityContext\InternalAPI\InternalMetaCallStackAPI;
use CodeDistortion\ClarityContext\Support\MetaCallStack;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;

/**
 * Test the InternalMetaCallStackAPI class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class InternalMetaCallStackAPIUnitTest extends PHPUnitTestCase
{
    /**
     * Test that the global meta call stack can be fetched, and is the same instance each time.
     *
     * @test
     *
     * @return void
     */
    public static function test_the_get_global_meta_call_stack_method(): void
    {
        $metaCallStack1 = InternalMetaCallStackAPI::getGlobalMetaCallStack();
        $metaCallStack2 = InternalMetaCallStackAPI::getGlobalMetaCallStack();

        self::assertInstanceOf(MetaCallStack::class, $metaCallStack1);
        self::assertSame($metaCallStack1, $metaCallStack2);
    }
}
