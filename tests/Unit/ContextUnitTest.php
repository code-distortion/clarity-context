<?php

namespace CodeDistortion\ClarityContext\Tests\Unit;

use CodeDistortion\ClarityContext\Context;
use CodeDistortion\ClarityContext\Exceptions\ClarityContextInitialisationException;
use CodeDistortion\ClarityContext\Settings;
use CodeDistortion\ClarityContext\Support\CallStack\CallStack;
use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\CallMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionCaughtMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionThrownMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\LastApplicationFrameMeta;
use CodeDistortion\ClarityContext\Support\Framework\Framework;
use CodeDistortion\ClarityContext\Support\InternalSettings;
use CodeDistortion\ClarityContext\Support\MetaCallStack;
use CodeDistortion\ClarityContext\Support\Support;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\LaravelConfigHelper;
use CodeDistortion\ClarityContext\Tests\TestSupport\PHPStackTraceHelper;
use CodeDistortion\ClarityContext\Tests\TestSupport\SimulateControlPackage;
use Exception;
use Throwable;

/**
 * Test the Context class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ContextUnitTest extends LaravelTestCase
{
    /**
     * Test updating of values in the Context object.
     *
     * @test
     *
     * @return void
     */
    public static function test_context_crud(): void
    {
        // CREATE a new Context object
        $exception = new Exception();
        $traceIdentifiers = ['' => 'abc'];
        $channels = ['stack'];
        $level = Settings::REPORTING_LEVEL_DEBUG;
        $report = true;
        $rethrow = false;
        $default = 'default1';

        $basePath = (string) realpath(base_path('../../../..'));
        $projectRootDir = str_replace('/', DIRECTORY_SEPARATOR, $basePath);

        $context = new Context(
            $exception,
            null,
            new MetaCallStack(),
            -1,
            $traceIdentifiers,
            $projectRootDir,
            $channels,
            $level,
            $report,
            $rethrow,
            $default,
        );

        self::assertSame(1, Context::CONTEXT_VERSION);
        self::assertSame($exception, $context->getException());
        self::assertSame($traceIdentifiers, $context->getTraceIdentifiers());
        // tested more below in test_generation_of_callstack_and_stack_trace_xxx methods
        self::assertInstanceOf(CallStack::class, $context->getCallStack());
        // tested more below in test_generation_of_callstack_and_stack_trace_xxx methods
        self::assertInstanceOf(CallStack::class, $context->getStackTrace());
        self::assertSame($channels, $context->getChannels());
        self::assertSame($level, $context->getLevel());
        self::assertSame($report, $context->getReport());
        self::assertSame($rethrow, $context->getRethrow());
        self::assertSame($default, $context->getDefault());

        $context->suppress();
        self::assertSame(false, $context->getReport());
        self::assertSame(false, $context->getRethrow());

        $context->setKnown([]);
        self::assertSame([], $context->getKnown());
        self::assertFalse($context->hasKnown());



        // UPDATE the Context object's values
        $traceIdentifiers = ['' => 'abc', 'def' => 'ghi'];
        $known = ['Ratione quis aliquid velit.'];
        $channels = ['daily'];
        $level = null;
        $report = false;
        $rethrow = true;
        $default = 'default2';

        $context
            ->setTraceIdentifiers($traceIdentifiers)
            ->setKnown($known)
            ->setChannels($channels)
            ->setLevel($level)
            ->setReport($report)
            ->setRethrow($rethrow)
            ->setDefault($default);

        self::assertSame($traceIdentifiers, $context->getTraceIdentifiers());
        self::assertSame($known, $context->getKnown());
        self::assertTrue($context->hasKnown());
        self::assertSame($channels, $context->getChannels());
        self::assertSame($level, $context->getLevel());
        self::assertSame($report, $context->getReport());
        self::assertSame($rethrow, $context->getRethrow());
        self::assertSame($default, $context->getDefault());

        $closure = fn() => true;
        $context->setRethrow($closure); // set a closure
        self::assertSame($closure, $context->getRethrow());

        $exception = new Exception();
        $context->setRethrow($exception); // set an exception
        self::assertSame($exception, $context->getRethrow());

        $context
            ->setReport() // without parameters
            ->setRethrow(); // without parameters
        self::assertSame(true, $context->getReport());
        self::assertSame(true, $context->getRethrow());

        $context
            ->dontReport()
            ->dontRethrow();
        self::assertSame(false, $context->getReport());
        self::assertSame(false, $context->getRethrow());



        // update the Context object with different types of values at the same time
        $context->setKnown('Aspernatur accusantium ut.', ['Quis delectus et ratione.']);
        self::assertSame(['Aspernatur accusantium ut.', 'Quis delectus et ratione.'], $context->getKnown());

        $context->setChannels('daily', ['something']);
        self::assertSame(['daily', 'something'], $context->getChannels());



        // set different log reporting levels
        $context->debug();
        self::assertSame(Settings::REPORTING_LEVEL_DEBUG, $context->getLevel());
        $context->info();
        self::assertSame(Settings::REPORTING_LEVEL_INFO, $context->getLevel());
        $context->notice();
        self::assertSame(Settings::REPORTING_LEVEL_NOTICE, $context->getLevel());
        $context->warning();
        self::assertSame(Settings::REPORTING_LEVEL_WARNING, $context->getLevel());
        $context->error();
        self::assertSame(Settings::REPORTING_LEVEL_ERROR, $context->getLevel());
        $context->critical();
        self::assertSame(Settings::REPORTING_LEVEL_CRITICAL, $context->getLevel());
        $context->alert();
        self::assertSame(Settings::REPORTING_LEVEL_ALERT, $context->getLevel());
        $context->emergency();
        self::assertSame(Settings::REPORTING_LEVEL_EMERGENCY, $context->getLevel());
    }



    /**
     * Test the retrieval of the callstack and stack trace from the Context object.
     *
     * @test
     *
     * @return void
     * @throws Exception Doesn't throw this, but phpcs expects this to be here.
     */
    public static function test_retrieval_of_callstack_and_stack_trace(): void
    {
        $context = new Context(
            new Exception(),
            null,
            new MetaCallStack(),
            -1,
            [],
            Framework::config()->getProjectRootDir(),
            [],
            null,
            true,
            false,
            null,
        );

        // the callstack and stack trace objects are cloned each time (the frames won't be)
        self::assertNotSame($context->getCallStack(), $context->getCallStack());
        self::assertNotSame($context->getStackTrace(), $context->getStackTrace());

        // the LAST frame from a callstack will be from this file
        /** @var Frame[] $callStack */
        $callStack = $context->getCallStack();
        $lastIndex = count($callStack) - 1;
        self::assertNotSame(__FILE__, $callStack[0]->getFile());
        self::assertSame(__FILE__, $callStack[$lastIndex]->getFile());

        // the FIRST frame from a stack trace will be from this file
        /** @var Frame[] $stackTrace */
        $stackTrace = $context->getStackTrace();
        $lastIndex = count($stackTrace) - 1;
        self::assertSame(__FILE__, $stackTrace[0]->getFile());
        self::assertNotSame(__FILE__, $stackTrace[$lastIndex]->getFile());
    }



    /**
     * Test the methods that fetch values from a Context object separately.
     *
     * @test
     *
     * @return void
     * @throws Exception Doesn't throw this, but phpcs expects this to be here.
     */
    public static function test_context_fetching_methods_separately(): void
    {
        $fakeId = mt_rand();

        $traceIdentifiers = ['' => 'abc', 'def' => 'ghi'];
        $known = ["Ratione quis aliquid velit. $fakeId"];
        $channels = ['stack'];
        $level = 'info';
        $report = true;
        $rethrow = false;
        $default = mt_rand();



        $callbacks = [];
        $callbacks[] = fn(Context $context, Throwable $e) => self::assertSame($e, $context->getException());
        $callbacks[] = fn(Context $context) => self::assertInstanceOf(CallStack::class, $context->getCallStack());
        $callbacks[] = fn(Context $context) => self::assertInstanceOf(CallStack::class, $context->getStackTrace());
        $callbacks[] = fn(Context $context) => self::assertSame($traceIdentifiers, $context->getTraceIdentifiers());
        $callbacks[] = fn(Context $context) => self::assertSame($known, $context->getKnown());
        $callbacks[] = fn(Context $context) => self::assertSame((bool) count($known), $context->hasKnown());
        $callbacks[] = fn(Context $context) => self::assertSame($channels, $context->getChannels());
        $callbacks[] = fn(Context $context) => self::assertSame($level, $context->getLevel());
        $callbacks[] = fn(Context $context) => self::assertSame($report, $context->getReport());
        $callbacks[] = fn(Context $context) => self::assertSame($rethrow, $context->getRethrow());
        $callbacks[] = fn(Context $context) => self::assertSame($default, $context->getDefault());
        $callbacks[] = fn(Context $context) => self::assertFalse($context->detailsAreWorthListing());



        foreach ($callbacks as $callback) {

            $e = new Exception();
            $context = new Context(
                $e,
                null,
                new MetaCallStack(),
                -1,
                $traceIdentifiers,
                Framework::config()->getProjectRootDir(),
                $channels,
                $level,
                $report,
                $rethrow,
                $default,
            );
            $context->setKnown($known);

            $callback($context, $e);
        }
    }



    /**
     * Test that the Context object generates callstacks and stack traces based on an exception.
     *
     * @test
     *
     * @return void
     */
    public static function test_generation_of_callstack_and_stack_trace_based_on_an_exception(): void
    {
        $exception = new Exception();

        $basePath = (string) realpath(base_path('../../../..'));
        $projectRootDir = str_replace('/', DIRECTORY_SEPARATOR, $basePath);

        $context = new Context(
            $exception,
            null,
            new MetaCallStack(),
            null,
            [],
            $projectRootDir,
            [],
            Settings::REPORTING_LEVEL_DEBUG,
            true,
            true,
            '',
        );



        // build a representation of the frames based on the exception's stack trace
        $exceptionStackTrace = array_reverse($exception->getTrace());
        $exceptionCallStackFrames = [];
        $function = '[top]';
        foreach ($exceptionStackTrace as $frame) {
            $exceptionCallStackFrames[] = [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $function,
            ];
            $function = $frame['function']; // shift the function by 1 frame
        }
        // add the exception's location as a frame
        $exceptionCallStackFrames[] = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'function' => $function,
        ];
        $exceptionStackTraceFrames = array_reverse($exceptionCallStackFrames);



        // build a representation of the frames based on Clarity's callstack
        $callstackFrames = [];
        foreach ($context->getCallStack() as $frame) {
            $callstackFrames[] = [
                'file' => $frame->getFile(),
                'line' => $frame->getLine(),
                'function' => $frame->getFunction(),
            ];
        }



        // build a representation of the frames based on Clarity's stack trace
        $stackTraceFrames = [];
        foreach ($context->getStackTrace() as $frame) {
            $stackTraceFrames[] = [
                'file' => $frame->getFile(),
                'line' => $frame->getLine(),
                'function' => $frame->getFunction(),
            ];
        }



        // check they're the same
        self::assertSame($exceptionCallStackFrames, $callstackFrames);
        self::assertSame($exceptionStackTraceFrames, $stackTraceFrames);
    }



    /**
     * Test that the Context object generates callstacks and stack traces based on a php stack trace.
     *
     * @test
     *
     * @return void
     */
    public static function test_generation_of_callstack_and_stack_trace_based_on_a_php_stack_trace(): void
    {
        $phpStackTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);

        $basePath = (string) realpath(base_path('../../../..'));
        $projectRootDir = str_replace('/', DIRECTORY_SEPARATOR, $basePath);

        $context = new Context(
            null,
            $phpStackTrace,
            new MetaCallStack(),
            -1,
            [],
            $projectRootDir,
            [],
            Settings::REPORTING_LEVEL_DEBUG,
            true,
            true,
            '',
        );



        // build a representation of the frames based on PHP's stack trace
        $phpCallstackFrames = [];
        $function = '[top]';
        foreach (array_reverse($phpStackTrace) as $frame) {
            $phpCallstackFrames[] = [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $function,
            ];
            $function = $frame['function']; // shift the function by 1 frame
        }
        $phpStackTraceFrames = array_reverse($phpCallstackFrames);



        // build a representation of the frames based on Clarity's callstack
        $callstackFrames = [];
        foreach ($context->getCallStack() as $frame) {
            $callstackFrames[] = [
                'file' => $frame->getFile(),
                'line' => $frame->getLine(),
                'function' => $frame->getFunction(),
            ];
        }



        // build a representation of the frames based on Clarity's stack trace
        $stackTraceFrames = [];
        foreach ($context->getStackTrace() as $frame) {
            $stackTraceFrames[] = [
                'file' => $frame->getFile(),
                'line' => $frame->getLine(),
                'function' => $frame->getFunction(),
            ];
        }



        // check they're the same
        self::assertSame($phpCallstackFrames, $callstackFrames);
        self::assertSame($phpStackTraceFrames, $stackTraceFrames);
    }



    /**
     * Test that a Context populates the CallMeta "caughtHere" value when built based on an exception.
     *
     * @test
     *
     * @return void
     */
    public static function test_the_context_class_can_identify_the_caught_here_frame_when_based_on_an_exception(): void
    {
        $catcherObjectId = 1;

        $exception = SimulateControlPackage::pushControlCallMetaAndGenerateException($catcherObjectId);

        $context = new Context(
            $exception,
            null,
            Support::getGlobalMetaCallStack(),
            $catcherObjectId,
            [],
            Framework::config()->getProjectRootDir(),
            [],
            Settings::REPORTING_LEVEL_DEBUG,
            true,
            true,
            '',
        );

        // check that the CallMeta "catches" the exception
        $callMeta = $context->getCallStack()->getMeta(CallMeta::class)[0] ?? null;
        self::assertInstanceOf(CallMeta::class, $callMeta);
        self::assertTrue($callMeta->wasCaughtHere());
    }



    /**
     * Test that a Context object can be built based on a php stack trace.
     *
     * @test
     *
     * @return void
     */
    public static function test_the_context_class_wont_identify_the_caught_here_frame_when_based_on_stack_trace(): void
    {
        $catcherObjectId = 1;

        SimulateControlPackage::pushControlCallMetaHere($catcherObjectId);

        $phpStackTrace = PHPStackTraceHelper::buildPHPStackTraceHere();

        $context = new Context(
            null,
            $phpStackTrace,
            Support::getGlobalMetaCallStack(),
            $catcherObjectId,
            [],
            Framework::config()->getProjectRootDir(),
            [],
            Settings::REPORTING_LEVEL_DEBUG,
            true,
            true,
            '',
        );

        // check that the CallMeta doesn't "catch" the exception (because no exception exists)
        $callMeta = $context->getCallStack()->getMeta(CallMeta::class)[0] ?? null;
        self::assertInstanceOf(CallMeta::class, $callMeta); // the CallMeta will exist
        self::assertFalse($callMeta->wasCaughtHere()); // but it won't have "caught" the exception, because
                                                       // there was no exception
    }



    /**
     * Test the code that determines whether the Context is "worth reporting".
     *
     * @test
     *
     * @return void
     */
    public static function test_worth_reporting(): void
    {
        $buildContext = function (MetaCallStack $metaCallStack, ?int $catcherObjectId = null): Context {
            return new Context(
                new Exception(),
                null,
                $metaCallStack,
                $catcherObjectId,
                [],
                Framework::config()->getProjectRootDir(),
                [],
                Settings::REPORTING_LEVEL_DEBUG,
                true,
                true,
                '',
            );
        };



        // when there is only a ExceptionThrownMeta and LastApplicationFrameMeta
        $context = $buildContext(new MetaCallStack());
        self::assertFalse($context->detailsAreWorthListing());

        // when there is a ContextMeta as well
        $metaCallStack = new MetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues(
            InternalSettings::META_DATA_TYPE__CONTEXT,
            null,
            ['hello'],
            0
        );

        $context = $buildContext($metaCallStack);
        self::assertTrue($context->detailsAreWorthListing());
    }



    /**
     * Ensure no meta-data objects are added when Clarity is disabled.
     *
     * @test
     * @dataProvider clarityEnabledDataProvider
     *
     * @param boolean $enabled Whether Clarity is enabled or not.
     * @return void
     */
    public static function test_that_meta_objects_arent_created_when_clarity_is_disabled(bool $enabled): void
    {
        $enabled
            ? LaravelConfigHelper::enableClarity()
            : LaravelConfigHelper::disableClarity();



        $catcherObjectId = 1;
        $clarityMeta = [
            'object-id' => $catcherObjectId,
            'known' => [],
        ];

        $metaCallStack = new MetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues(
            InternalSettings::META_DATA_TYPE__CONTROL_CALL,
            $catcherObjectId,
            [$clarityMeta],
            0
        );
        $metaCallStack->pushMultipleMetaDataValues(
            InternalSettings::META_DATA_TYPE__CONTEXT,
            null,
            ['some context'],
            0
        );

        $exception = new Exception();

        $context = new Context(
            $exception,
            null,
            $metaCallStack,
            $catcherObjectId,
            [],
            Framework::config()->getProjectRootDir(),
            [],
            Settings::REPORTING_LEVEL_DEBUG,
            true,
            true,
            '',
        );



        $callStack = $context->getCallStack();

        if ($enabled) {
            self::assertCount(5, $callStack->getMeta());
            self::assertCount(1, $callStack->getMeta(CallMeta::class));
            self::assertCount(1, $callStack->getMeta(ContextMeta::class));
            self::assertCount(1, $callStack->getMeta(LastApplicationFrameMeta::class));
            self::assertCount(1, $callStack->getMeta(ExceptionThrownMeta::class));
            self::assertCount(1, $callStack->getMeta(ExceptionCaughtMeta::class));
        } else {
            // no meta objects when disabled
            self::assertCount(0, $callStack->getMeta());
        }
    }

    /**
     * DataProvider for test_when_clarity_is_disabled().
     *
     * @return array<array<string,boolean>>
     */
    public static function clarityEnabledDataProvider(): array
    {
        return [
            ['enabled' => true],
            ['enabled' => false],
        ];
    }



    /**
     * Check what happens when an invalid meta-data type is encountered.
     *
     * @test
     *
     * @return void
     */
    public static function test_when_an_invalid_meta_data_type_is_encountered(): void
    {
        $metaCallStack = new MetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues('invalid', null, ['some context'], 0);

        $context = new Context(
            new Exception(),
            null,
            $metaCallStack,
            null,
            [],
            Framework::config()->getProjectRootDir(),
            [],
            Settings::REPORTING_LEVEL_DEBUG,
            true,
            true,
            '',
        );

        // check that an exception is thrown
        $caughtException = false;
        try {
            $context->getCallStack();
        } catch (ClarityContextInitialisationException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);
    }
}
