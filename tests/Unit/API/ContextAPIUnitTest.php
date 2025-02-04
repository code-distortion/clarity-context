<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\API;

use CodeDistortion\ClarityContext\API\ContextAPI;
use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Settings;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionCaughtMeta;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\LaravelConfigHelper;
use CodeDistortion\ClarityContext\Tests\TestSupport\PHPStackTraceHelper;
use CodeDistortion\ClarityContext\Tests\TestSupport\SimulateControlPackage;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the ContextAPI class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ContextAPIUnitTest extends LaravelTestCase
{
    /**
     * Test that the buildContextHere() builds a Context object as expected.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_build_context_here_method_has_correct_values(): void
    {
        LaravelConfigHelper::updateChannelsWhenKnown(['channel-when-known']);
        LaravelConfigHelper::updateChannelsWhenNotKnown(['channel-when-not-known']);
        LaravelConfigHelper::updateLevelWhenKnown(Settings::REPORTING_LEVEL_DEBUG);
        LaravelConfigHelper::updateLevelWhenNotKnown(Settings::REPORTING_LEVEL_WARNING);

        $phpStackTrace = PHPStackTraceHelper::buildPHPStackTraceHere();
        $context = ContextAPI::buildContextHere();

        self::assertSame(count($phpStackTrace), $context->getCallStack()->count());
        self::assertSame(['channel-when-not-known'], $context->getChannels());
        self::assertNull($context->getLevel());
    }





    /**
     * Test that the buildContextHere() builds a Context object as expected.
     *
     * @test
     * @dataProvider phpStackTraceDataProvider
     *
     * @param integer $framesBack      The number of frames to go back.
     * @param boolean $expectException Whether an exception is expected or not.
     * @return void
     */
    #[Test]
    #[DataProvider('phpStackTraceDataProvider')]
    public static function test_build_context_here_method_builds_based_on_frames_back(
        int $framesBack,
        bool $expectException
    ): void {

        $caughtException = false;
        try {

            $context = ContextAPI::buildContextHere($framesBack);

            $phpStackTrace = PHPStackTraceHelper::buildPHPStackTraceHere();
            self::assertSame(count($phpStackTrace) - $framesBack, $context->getCallStack()->count());

        } catch (ClarityContextRuntimeException) {
            $caughtException = true;
        }
        self::assertSame($expectException, $caughtException);
    }

    /**
     * DataProvider for test_build_context_method_for_php_stack_trace().
     *
     * @return array<array<string, integer|boolean>>
     */
    public static function phpStackTraceDataProvider(): array
    {
        return [
            ['framesBack' => 100000, 'expectException' => true],
            ['framesBack' => 1, 'expectException' => false],
            ['framesBack' => 0, 'expectException' => false],
            ['framesBack' => -1, 'expectException' => true],
        ];
    }





    /**
     * Test that the buildContextFromException() builds a Context object as expected.
     *
     * @test
     * @dataProvider buildContextFromExceptionDataProvider
     *
     * @param boolean|null $report  Whether to report the exception or not.
     * @param boolean      $isKnown Whether to say the exception was known or not.
     * @param boolean      $catch   Whether to pretend to catch the exception or not.
     * @return void
     */
    #[Test]
    #[DataProvider(methodName: 'buildContextFromExceptionDataProvider')]
    public static function test_build_context_from_exception_method(?bool $report, bool $isKnown, bool $catch): void
    {
        LaravelConfigHelper::updateReportSetting($report);
        LaravelConfigHelper::updateChannelsWhenKnown(['channel-when-known']);
        LaravelConfigHelper::updateChannelsWhenNotKnown(['channel-when-not-known']);
        LaravelConfigHelper::updateLevelWhenKnown(Settings::REPORTING_LEVEL_DEBUG);
        LaravelConfigHelper::updateLevelWhenNotKnown(Settings::REPORTING_LEVEL_WARNING);



        $catcherObjectId = 1;
        SimulateControlPackage::pushControlCallMetaHere($catcherObjectId, [], 1);

        $phpStackTrace = PHPStackTraceHelper::buildPHPStackTraceHere();



        $exception = new Exception();
        $context = ContextAPI::buildContextFromException($exception, $isKnown, $catch ? $catcherObjectId : null);




        self::assertSame($exception, $context->getException());
        self::assertSame(count($phpStackTrace), $context->getCallStack()->count());



        self::assertSame($report ?? true, $context->getReport());

        // $isKnown doesn't affect the $context->hasKnown() setting, instead it affects the channels and level chosen
        if ($isKnown) {
            self::assertSame(['channel-when-known'], $context->getChannels());
            self::assertSame(Settings::REPORTING_LEVEL_DEBUG, $context->getLevel());
        } else {
            self::assertSame(['channel-when-not-known'], $context->getChannels());
            self::assertSame(Settings::REPORTING_LEVEL_WARNING, $context->getLevel());
        }

        self::assertCount($catch ? 1 : 0, $context->getCallStack()->getMeta(ExceptionCaughtMeta::class));



        // test that the exception's context has been remembered
        self::assertSame($context, ContextAPI::getRememberedExceptionContext($exception));
    }

    /**
     * DataProvider for test_build_context_method_from_an_exception().
     *
     * @return array<array<string,boolean|null>>
     */
    public static function buildContextFromExceptionDataProvider(): array
    {
        return [
            ['report' => false, 'isKnown' => false, 'catch' => false],
            ['report' => false, 'isKnown' => false, 'catch' => true],
            ['report' => false, 'isKnown' => true, 'catch' => false],
            ['report' => false, 'isKnown' => true, 'catch' => true],
            ['report' => true, 'isKnown' => false, 'catch' => false],
            ['report' => true, 'isKnown' => false, 'catch' => true],
            ['report' => true, 'isKnown' => true, 'catch' => false],
            ['report' => true, 'isKnown' => true, 'catch' => true],
            ['report' => null, 'isKnown' => false, 'catch' => false],
            ['report' => null, 'isKnown' => false, 'catch' => true],
            ['report' => null, 'isKnown' => true, 'catch' => false],
            ['report' => null, 'isKnown' => true, 'catch' => true],
        ];
    }





    /**
     * Test that an exception's context object can be remembered, retrieved, and forgotten.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_the_context_remember_retrieve_and_forget_methods(): void
    {
        self::assertNull(ContextAPI::getLatestExceptionContext());

        $exception1 = new Exception();
        $exception2 = new Exception();

        self::assertNull(ContextAPI::getLatestExceptionContext());
        self::assertNull(ContextAPI::getRememberedExceptionContext($exception1));
        self::assertNull(ContextAPI::getRememberedExceptionContext($exception2));

        $context1 = ContextAPI::buildContextFromException($exception1);

        self::assertSame($context1, ContextAPI::getLatestExceptionContext());
        self::assertSame($context1, ContextAPI::getRememberedExceptionContext($exception1));
        self::assertNull(ContextAPI::getRememberedExceptionContext($exception2));

        $context2 = ContextAPI::buildContextFromException($exception2);

        self::assertSame($context2, ContextAPI::getLatestExceptionContext());
        self::assertSame($context1, ContextAPI::getRememberedExceptionContext($exception1));
        self::assertSame($context2, ContextAPI::getRememberedExceptionContext($exception2));

        ContextAPI::forgetExceptionContext($exception2);

        self::assertSame($context1, ContextAPI::getLatestExceptionContext());
        self::assertSame($context1, ContextAPI::getRememberedExceptionContext($exception1));
        self::assertNull(ContextAPI::getRememberedExceptionContext($exception2));

        ContextAPI::forgetExceptionContext($exception1);

        self::assertNull(ContextAPI::getLatestExceptionContext());
        self::assertNull(ContextAPI::getRememberedExceptionContext($exception1));
        self::assertNull(ContextAPI::getRememberedExceptionContext($exception2));
    }
}
