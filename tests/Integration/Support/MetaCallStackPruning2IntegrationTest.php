<?php

namespace CodeDistortion\ClarityContext\Tests\Integration\Support;

use CodeDistortion\ClarityContext\Clarity;
use CodeDistortion\ClarityContext\Support\CallStack\CallStack;
use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\SomeOtherClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test that the MetaCallStack class prunes frames properly.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class MetaCallStackPruning2IntegrationTest extends LaravelTestCase
{
    /**
     * Test that meta-data gets pruned properly, when a method is called on *different* objects of the same class.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_frame_pruning_when_calling_an_object_method(): void
    {
        $objects = [];
        for ($count = 0; $count <= 1; $count++) {

            $context = $count == 0
                ? 'something'
                : null;

            // create a SomeOtherClass, and store a reference to it (to stop its object-id from being reused)
            $objects[$count] = new SomeOtherClass();
            $callStack = $objects[$count]->addContextAndCheckMeta($context); // add some context (or not)
            self::assertSame($context, self::getContextValue($callStack));
        }
    }



    /**
     * Test that meta-data gets pruned properly, when a method is called on a class static-method.
     *
     * NOTE: Because the calls to the static method within the loop occur on the same line, the frames look the same to
     * Clarity. Unfortunately it can't tell that they're *different* calls to the same method, so it won't prune the
     * "something" context from the first loop iteration.
     *
     * This test just confirms this bleeding :[, as it is "known" behaviour.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_frame_pruning_when_calling_a_class_static_method(): void
    {
        for ($count = 0; $count <= 1; $count++) {

            $context = $count == 0
                ? 'something'
                : null;

            $callStack = SomeOtherClass::addContextAndCheckMetaStatic($context); // add some context (or not)
            self::assertSame('something', self::getContextValue($callStack)); // is always "something"
        }
    }



    /**
     * Test that meta-data gets pruned properly, when a closure is called.
     *
     * NOTE: Because the calls to the closure within the loop occur on the same line, the frames look the same to
     * Clarity. Unfortunately it can't tell that they're *different* calls to the closure, so it won't prune the
     * "something" context from the first loop iteration.
     *
     * This test just confirms this bleeding :[, as it is "known" behaviour.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_frame_pruning_when_calling_a_closure(): void
    {
        $callback = function (?string $context) {

            if ($context) {
                Clarity::context($context);
            }

            return Clarity::buildContextHere()->getCallStack();
        };

        for ($count = 0; $count <= 1; $count++) {

            $context = $count == 0
                ? 'something'
                : null;

            $callStack = $callback($context); // add some context (or not)
            self::assertSame('something', self::getContextValue($callStack)); // is always "something"
        }
    }



    /**
     * Get the context string or array from the last frame in a CallStack.
     *
     * @param CallStack $callStack The CallStack to check.
     * @return string|mixed[]|null
     */
    private static function getContextValue(CallStack $callStack): string|array|null
    {
        $index = count($callStack) - 1;
        /** @var Frame $frame */
        $frame = $callStack[$index];
        /** @var ContextMeta[] $contextMetas */
        $contextMetas = $frame->getMeta(ContextMeta::class);
        return count($contextMetas)
            ? $contextMetas[0]->getContext()
            : null;
    }
}
