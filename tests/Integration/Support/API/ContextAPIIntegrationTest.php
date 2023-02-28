<?php

namespace CodeDistortion\ClarityContext\Tests\Integration\Support\API;

use CodeDistortion\ClarityContext\API\ContextAPI;
use CodeDistortion\ClarityContext\Settings;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\CallMeta;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\LaravelConfigHelper;
use CodeDistortion\ClarityContext\Tests\TestSupport\PHPStackTraceHelper;
use CodeDistortion\ClarityContext\Tests\TestSupport\SimulateControlPackage;

/**
 * Test the ContextAPI class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ContextAPIIntegrationTest extends LaravelTestCase
{
    /**
     * Test that the Context object that ContextAPI builds (based on the current PHP stack trace) contains the required
     * values.
     *
     * @test
     *
     * @return void
     */
    public static function test_build_context_method_for_php_stack_trace_and_check_output_context_object(): void
    {
        // add some config settings to make sure they're picked up later
        LaravelConfigHelper::updateChannelsWhenNotKnown(['channels-when-not-known']);
        LaravelConfigHelper::updateLevelWhenNotKnown(Settings::REPORTING_LEVEL_EMERGENCY);

        // pretend Control has called some code
        SimulateControlPackage::pushControlCallMetaHere(1);



        // build the Context object
        $phpStackTrace = PHPStackTraceHelper::buildPHPStackTraceHere();
        $context = ContextAPI::buildContextHere();



        // check the Context object…

        // check that the stack trace was used
        self::assertSame(count($phpStackTrace), $context->getStackTrace()->count());

        // check the channels that were resolved
        self::assertSame(['channels-when-not-known'], $context->getChannels());

        // check the level that was resolved
        self::assertNull($context->getLevel());

        // check the report and rethrow settings
        self::assertSame(false, $context->getReport());
        self::assertSame(false, $context->getRethrow());

        // check that the CallMeta doesn't "catch" the exception (because no exception exists)
        $callMeta = $context->getCallStack()->getMeta(CallMeta::class)[0] ?? null;
        self::assertInstanceOf(CallMeta::class, $callMeta); // the CallMeta will exist
        self::assertFalse($callMeta->wasCaughtHere()); // but it won't have "caught" the exception, because
                                                       // there was no exception
    }



    /**
     * Test that the Context object that ContextAPI builds (based on an exception) contains the required values.
     *
     * @test
     *
     * @return void
     */
    public static function test_build_context_method_for_an_exception_and_check_output_context_object(): void
    {
        // add some config settings to make sure they're picked up later
        LaravelConfigHelper::updateChannelsWhenNotKnown(['channels-when-not-known']);
        LaravelConfigHelper::updateLevelWhenNotKnown(Settings::REPORTING_LEVEL_EMERGENCY);

        // pretend Control has called some code
        $exception = SimulateControlPackage::pushControlCallMetaAndGenerateException(1);



        // build the Context object
        $context = ContextAPI::buildContextFromException($exception, false, 1);



        // check the Context object…

        // check that the exception was used
        self::assertSame($exception, $context->getException());
        self::assertSame(count($exception->getTrace()) + 1, $context->getStackTrace()->count());

        // check the channels that were resolved
        self::assertSame(['channels-when-not-known'], $context->getChannels());

        // check the level that was resolved
        self::assertSame(Settings::REPORTING_LEVEL_EMERGENCY, $context->getLevel());

        // check the report and rethrow settings
        self::assertSame(true, $context->getReport());
        self::assertSame(false, $context->getRethrow());

        // check that the $catcherObjectId was used
        // (incidentally checks that the CallMeta "catches" the exception, but that's tested in ContextUnitTest)
        $callMeta = $context->getCallStack()->getMeta(CallMeta::class)[0] ?? null;
        self::assertInstanceOf(CallMeta::class, $callMeta);
        self::assertTrue($callMeta->wasCaughtHere());
    }
}
