<?php

namespace CodeDistortion\ClarityContext\Tests\Integration\Support\Context\CallStack;

use CodeDistortion\ClarityContext\API\ContextAPI;
use CodeDistortion\ClarityContext\Clarity;
use CodeDistortion\ClarityContext\Context;
use CodeDistortion\ClarityContext\Settings;
use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\CallMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionCaughtMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionThrownMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\LastApplicationFrameMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\Meta;
use CodeDistortion\ClarityContext\Support\MetaCallStack;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\SimulateControlPackage;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Throwable;

/**
 * Test the generation of CallStack and Frame objects.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CallStackAndFrameGenerationIntegrationTest extends LaravelTestCase
{
    /**
     * Test the functionality of the code that retrieves Meta objects from the CallStack.
     *
     * @test
     *
     * @return void
     * @throws Exception Doesn't throw this, but phpcs expects this to be here.
     */
    public static function test_the_retrieval_of_meta_objects(): void
    {
        Clarity::context('ONE');
        Clarity::context(['111' => '111']);
        Clarity::context('TWO');
        Clarity::context(['222' => '222']);

        // simulate Control running and catching an exception
        $catcherObjectId = 1;
        $e = SimulateControlPackage::pushControlCallMetaAndGenerateException($catcherObjectId);
        $context = SimulateControlPackage::buildContext($catcherObjectId, $e);
        ContextAPI::rememberExceptionContext($e, $context);

        $context = Clarity::getExceptionContext($e);
        $callStack = $context->getCallStack();



        // retrieve all meta
        self::assertCount(8, $callStack->getMeta());

        self::assertCount(1, $callStack->getMeta(ExceptionThrownMeta::class));
        self::assertCount(1, $callStack->getMeta(ExceptionCaughtMeta::class));
        self::assertCount(1, $callStack->getMeta(LastApplicationFrameMeta::class));
        self::assertCount(1, $callStack->getMeta(CallMeta::class));
        self::assertCount(4, $callStack->getMeta(ContextMeta::class));

        self::assertCount(5, $callStack->getMeta(LastApplicationFrameMeta::class, ContextMeta::class));
        self::assertCount(5, $callStack->getMeta([LastApplicationFrameMeta::class, ContextMeta::class]));
        self::assertCount(5, $callStack->getMeta(LastApplicationFrameMeta::class, [ContextMeta::class]));
        self::assertCount(5, $callStack->getMeta([LastApplicationFrameMeta::class], ContextMeta::class));
        self::assertCount(5, $callStack->getMeta([LastApplicationFrameMeta::class], [ContextMeta::class]));

        // check that the ContextMetas don't get doubled up
        self::assertCount(8, $callStack->getMeta(LastApplicationFrameMeta::class, Meta::class));

        /** @phpstan-ignore-next-line */
        self::assertCount(0, $callStack->getMeta(NonExistantClass::class));
    }



    /**
     * Test that a CallStack is generated with the desired Frames and Meta objects, based on a thrown exception.
     *
     * @test
     *
     * @return void
     */
    public static function test_callstack_frames_and_meta_objects_when_built_for_a_thrown_exception(): void
    {
        Clarity::context('hello');
        Clarity::context(['a' => 'b']);

        // simulate Control running and catching an exception
        $catcherObjectId = 1;
        $e = SimulateControlPackage::pushControlCallMetaAndGenerateException($catcherObjectId);
        $context = SimulateControlPackage::buildContext($catcherObjectId, $e);
        ContextAPI::rememberExceptionContext($e, $context);


        // then retrieve it using Clarity
        $context = Clarity::getExceptionContext($e);
        $callStack = $context->getCallStack();

        // test that the callstack SeekableIterator object has been rewound
        self::assertSame($callStack[0], $callStack->current());

        // FRAMES
        self::assertInstanceOf(Frame::class, $callStack->getExceptionThrownFrame());
        self::assertNotNull($callStack->getExceptionThrownFrameIndex());

        self::assertInstanceOf(Frame::class, $callStack->getExceptionCaughtFrame());
        self::assertNotNull($callStack->getExceptionCaughtFrameIndex());

        self::assertInstanceOf(Frame::class, $callStack->getLastApplicationFrame());
        self::assertNotNull($callStack->getLastApplicationFrameIndex());

        // META OBJECTS
        self::assertCount(6, $callStack->getMeta(Meta::class));
        self::assertCount(1, $callStack->getMeta(ExceptionThrownMeta::class));
        self::assertCount(1, $callStack->getMeta(ExceptionCaughtMeta::class));
        self::assertCount(1, $callStack->getMeta(LastApplicationFrameMeta::class));
        self::assertCount(1, $callStack->getMeta(CallMeta::class));
        self::assertCount(2, $callStack->getMeta(ContextMeta::class));

        /** @var ContextMeta[] $contextMetas */
        $contextMetas = $callStack->getMeta(ContextMeta::class);
        self::assertCount(2, $contextMetas);

        self::assertSame(__FILE__, $contextMetas[0]->getFile());
        self::assertSame('hello', $contextMetas[0]->getContext());

        self::assertSame(__FILE__, $contextMetas[1]->getFile());
        self::assertSame(['a' => 'b'], $contextMetas[1]->getContext());

        /** @var CallMeta[] $callMetas */
        $callMetas = $callStack->getMeta(CallMeta::class);
        self::assertCount(1, $callMetas);
        self::assertSame(SimulateControlPackage::getClassFile(), $callMetas[0]->getFile());

        /** @var ExceptionThrownMeta[] $exceptionThrownMetas */
        $exceptionThrownMetas = $callStack->getMeta(ExceptionThrownMeta::class);
        self::assertCount(1, $exceptionThrownMetas);
        self::assertSame(SimulateControlPackage::getClassFile(), $exceptionThrownMetas[0]->getFile());

        /** @var ExceptionCaughtMeta[] $exceptionCaughtMetas */
        $exceptionCaughtMetas = $callStack->getMeta(ExceptionCaughtMeta::class);
        self::assertCount(1, $exceptionCaughtMetas);
        self::assertSame(SimulateControlPackage::getClassFile(), $exceptionCaughtMetas[0]->getFile());

        /** @var LastApplicationFrameMeta[] $lastApplicationFrameMetas */
        $lastApplicationFrameMetas = $callStack->getMeta(LastApplicationFrameMeta::class);
        self::assertCount(1, $lastApplicationFrameMetas);
        self::assertSame(SimulateControlPackage::getClassFile(), $lastApplicationFrameMetas[0]->getFile());
    }

    /**
     * Test that a CallStack is generated with the desired Frames and Meta objects, based on a passed exception.
     *
     * @test
     *
     * @return void
     */
    public static function test_callstack_frames_and_meta_objects_when_built_from_passed_exception(): void
    {
        Clarity::context('hello');
        Clarity::context(['a' => 'b']);

        // build a fresh context directly
        $context = Clarity::getExceptionContext(new Exception());
        $callStack = $context->getCallStack();

        // FRAMES
        self::assertInstanceOf(Frame::class, $callStack->getExceptionThrownFrame());
        self::assertNotNull($callStack->getExceptionThrownFrameIndex());

        self::assertNull($callStack->getExceptionCaughtFrame());
        self::assertNull($callStack->getExceptionCaughtFrameIndex());

        self::assertInstanceOf(Frame::class, $callStack->getLastApplicationFrame());
        self::assertNotNull($callStack->getLastApplicationFrameIndex());

        // META OBJECTS
        self::assertCount(4, $callStack->getMeta(Meta::class));
        self::assertCount(1, $callStack->getMeta(ExceptionThrownMeta::class));
        self::assertCount(0, $callStack->getMeta(ExceptionCaughtMeta::class));
        self::assertCount(1, $callStack->getMeta(LastApplicationFrameMeta::class));
        self::assertCount(0, $callStack->getMeta(CallMeta::class));
        self::assertCount(2, $callStack->getMeta(ContextMeta::class));
    }

    /**
     * Test that a CallStack is generated with the desired Frames and Meta objects, when not based on an exception.
     *
     * @test
     *
     * @return void
     */
    public static function test_callstack_frames_and_meta_objects_when_not_built_from_an_exception(): void
    {
        Clarity::context('hello');
        Clarity::context(['a' => 'b']);

        $context = Clarity::buildContextHere();
        $callStack = $context->getCallStack();

        // FRAMES
        self::assertNull($callStack->getExceptionThrownFrame());
        self::assertNull($callStack->getExceptionThrownFrameIndex());

        self::assertNull($callStack->getExceptionCaughtFrame());
        self::assertNull($callStack->getExceptionCaughtFrameIndex());

        self::assertInstanceOf(Frame::class, $callStack->getLastApplicationFrame());
        self::assertNotNull($callStack->getLastApplicationFrameIndex());

        // META OBJECTS
        self::assertCount(3, $callStack->getMeta(Meta::class));
        self::assertCount(0, $callStack->getMeta(ExceptionThrownMeta::class));
        self::assertCount(0, $callStack->getMeta(ExceptionCaughtMeta::class));
        self::assertCount(1, $callStack->getMeta(LastApplicationFrameMeta::class));
        self::assertCount(0, $callStack->getMeta(CallMeta::class));
        self::assertCount(2, $callStack->getMeta(ContextMeta::class));
    }



    /**
     * Test what happens when the project-root can't be resolved for some reason.
     *
     * @test
     *
     * @return void
     */
    public static function test_what_happens_when_the_project_root_cant_be_resolved(): void
    {
        $e = null;
        try {
            // generate an exception to use below
            /** @phpstan-ignore-next-line */
            app()->make(NonExistantClass::class);
        } catch (Throwable $e) {
        }
        /** @var Exception $e */

        $context = new Context(
            $e,
            null,
            new MetaCallStack(),
            -1,
            [],
            '', // <<< no project root dir
            ['stack'],
            Settings::REPORTING_LEVEL_DEBUG,
            false,
            false,
            null,
        );
        $callStack = $context->getCallStack();

        self::assertCount(1, $callStack->getMeta(LastApplicationFrameMeta::class));

        $containerFile = '/vendor/laravel/framework/src/Illuminate/Container/Container.php';
        $containerFile = str_replace('/', DIRECTORY_SEPARATOR, $containerFile);

        // the vendor directory can't be determined properly, so it picks the last frame as the application frame
        $frame = $callStack->getLastApplicationFrame();
        self::assertInstanceOf(Frame::class, $frame);
        self::assertStringEndsWith($containerFile, $frame->getFile());
        self::assertStringEndsWith($containerFile, $frame->getProjectFile());
    }



    /**
     * Test that the Meta objects match the frames.
     *
     * @test
     *
     * @return void
     */
    public static function test_that_meta_matches_the_frames(): void
    {
        // simulate Control running and catching an exception
        $catcherObjectId = 1;
        $e = SimulateControlPackage::pushControlCallMetaAndGenerateException($catcherObjectId);
        $context = SimulateControlPackage::buildContext($catcherObjectId, $e);

        foreach ($context->getCallStack() as $frame) {

            $metaCount = count($frame->getMeta(LastApplicationFrameMeta::class));
            $frame->isLastApplicationFrame()
                ? self::assertSame(1, $metaCount)
                : self::assertSame(0, $metaCount);

            $metaCount = count($frame->getMeta(ExceptionThrownMeta::class));
            $frame->exceptionWasThrownHere()
                ? self::assertSame(1, $metaCount)
                : self::assertSame(0, $metaCount);

            $metaCount = count($frame->getMeta(ExceptionCaughtMeta::class));
            $frame->exceptionWasCaughtHere()
                ? self::assertSame(1, $metaCount)
                : self::assertSame(0, $metaCount);
        }
    }



    /**
     * Test the generation and retrieval of the ExceptionThrownMeta object from the callstack.
     *
     * @test
     *
     * @return void
     * @throws Exception Doesn't throw this, but phpcs expects this to be here.
     */
    public static function test_exception_thrown_meta(): void
    {
        $callback = function (Context $context, Exception $e) {

            $exceptionTrace = array_reverse(self::getExceptionCallStackArray($e));
            $frame = $exceptionTrace[0];

            $callStack = $context->getCallStack();

            $frameIndex = count($callStack) - 1;

            /** @var Frame $currentFrame */
            $currentFrame = $callStack[$frameIndex];

            self::assertSame($currentFrame, $callStack->getExceptionThrownFrame());
            self::assertTrue(!is_null($callStack->getExceptionThrownFrameIndex()));

            self::assertSame($currentFrame, $context->getStackTrace()->getExceptionThrownFrame());
            self::assertTrue(!is_null($context->getStackTrace()->getExceptionThrownFrameIndex()));

            $metaObjects1 = $currentFrame->getMeta(ExceptionThrownMeta::class);
            $metaObjects2 = $callStack->getMeta(ExceptionThrownMeta::class);

            self::assertCount(1, $metaObjects1);
            self::assertSame($metaObjects1, $metaObjects2);

            /** @var ExceptionThrownMeta $meta1 */
            $meta1 = $metaObjects1[0];

            self::assertInstanceOf(ExceptionThrownMeta::class, $meta1);

            self::assertSame($frame['file'], $meta1->getFile());
            self::assertSame($frame['line'], $meta1->getLine());

            $context->getException() instanceof BindingResolutionException
                ? null // the line inside the vendor directory may change
                : self::assertSame(__LINE__ + 16, $meta1->getLine()); // last frame is an APPLICATION file
        };

        // test when the last frame is a VENDOR frame
        try {
            // generate an exception to use below
            /** @phpstan-ignore-next-line */
            app()->make(NonExistantClass::class);
        } catch (BindingResolutionException $e) {
            $context = ContextAPI::buildContextFromException($e);
            $callback($context, $e);
        }

        // test when the last frame is an APPLICATION frame
        try {
            // generate an exception to use below
            throw new Exception();
        } catch (Exception $e) {
            $context = ContextAPI::buildContextFromException($e);
            $callback($context, $e);
        }
    }



    /**
     * Test the generation and retrieval of the ExceptionCaughtMeta object from the callstack.
     *
     * @test
     *
     * @return void
     * @throws Exception Doesn't throw this, but phpcs expects this to be here.
     */
    public static function test_exception_caught_meta(): void
    {
        $callback = function (Context $context) {

            $callStack = $context->getCallStack();
            $trace = $context->getStackTrace();

            /** @var Frame $currentFrame */
            $currentFrame = null;
            foreach ($context->getCallStack() as $tempFrame) {
                if ($tempFrame->exceptionWasCaughtHere()) {
                    $currentFrame = $tempFrame;
                    break;
                }
            }

            self::assertNotNull($currentFrame);

            self::assertSame($currentFrame, $callStack->getExceptionCaughtFrame());
            self::assertTrue(!is_null($callStack->getExceptionCaughtFrameIndex()));

            self::assertSame($currentFrame, $trace->getExceptionCaughtFrame());
            self::assertTrue(!is_null($trace->getExceptionCaughtFrameIndex()));

            /** @var Frame $currentFrame */
            $metaObjects1 = $currentFrame->getMeta(ExceptionCaughtMeta::class);
            $metaObjects2 = $callStack->getMeta(ExceptionCaughtMeta::class);

            self::assertCount(1, $metaObjects1);
            self::assertSame($metaObjects1, $metaObjects2);

            /** @var ExceptionCaughtMeta $meta1 */
            $meta1 = $metaObjects1[0];

            self::assertInstanceOf(ExceptionCaughtMeta::class, $meta1);
        };

        // simulate Control running and catching an exception
        $catcherObjectId = 1;
        SimulateControlPackage::pushControlCallMetaAndGenerateException($catcherObjectId, [], 1);

        // test when the last frame is a VENDOR frame
        try {
            // generate an exception to use below
            /** @phpstan-ignore-next-line */
            app()->make(NonExistantClass::class);
        } catch (BindingResolutionException $e) {
            $context = ContextAPI::buildContextFromException($e, false, $catcherObjectId);
            $callback($context);
        }

        // test when the last frame is an APPLICATION frame
        try {
            // generate an exception to use below
            throw new Exception();
        } catch (Exception $e) {
            $context = ContextAPI::buildContextFromException($e, false, $catcherObjectId);
            $callback($context);
        }
    }



    /**
     * Test the generation and retrieval of the LastApplicationFrameMeta object from the callstack.
     *
     * @test
     *
     * @return void
     * @throws Exception Doesn't throw this, but phpcs expects this to be here.
     */
    public static function test_last_application_frame_meta(): void
    {
        $callback = function (Context $context, Exception $e) {

            $path = 'tests/Integration/Support/Context/CallStack/CallStackAndFrameGenerationIntegrationTest.php';
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

            // find the last application (i.e. non-vendor) frame
            $frame = null;
            $frameIndex = 0;
            $count = 0;
            $exceptionTrace = array_reverse(self::getExceptionCallStackArray($e));
            foreach ($exceptionTrace as $tempFrame) {

                $file = is_string($tempFrame['file'] ?? null)
                    ? $tempFrame['file']
                    : '';

                if (mb_substr($file, - mb_strlen($path)) == $path) {
                    $frame = $tempFrame;
                    $frameIndex = (count($exceptionTrace) - 1) - $count;

                    break;
                }
                $count++;
            }
            self::assertNotNull($frame);



            $callStack = $context->getCallStack();
            /** @var Frame $currentFrame */
            $currentFrame = $callStack[$frameIndex];

            self::assertSame($currentFrame, $callStack->getLastApplicationFrame());
            self::assertTrue(!is_null($callStack->getLastApplicationFrameIndex()));

            self::assertSame($currentFrame, $context->getStackTrace()->getLastApplicationFrame());
            self::assertTrue(!is_null($context->getStackTrace()->getLastApplicationFrameIndex()));

            $metaObjects1 = $currentFrame->getMeta(LastApplicationFrameMeta::class);
            $metaObjects2 = $callStack->getMeta(LastApplicationFrameMeta::class);

            self::assertCount(1, $metaObjects1);
            self::assertSame($metaObjects1, $metaObjects2);

            /** @var LastApplicationFrameMeta $meta1 */
            $meta1 = $metaObjects1[0];

            self::assertInstanceOf(LastApplicationFrameMeta::class, $meta1);

            self::assertSame($frame['file'], $meta1->getFile());
            self::assertSame($frame['line'], $meta1->getLine());

            self::assertSame(__FILE__, $meta1->getfile());

            $context->getException() instanceof BindingResolutionException
                ? self::assertSame(__LINE__ + 8, $meta1->getLine()) // last frame is a VENDOR file
                : self::assertSame(__LINE__ + 16, $meta1->getLine()); // last frame is an APPLICATION file
        };

        // test when the last frame is a VENDOR frame
        try {
            // generate an exception to use below
            /** @phpstan-ignore-next-line */
            app()->make(NonExistantClass::class);
        } catch (BindingResolutionException $e) {
            $context = ContextAPI::buildContextFromException($e);
            $callback($context, $e);
        }

        // test when the last frame is an APPLICATION frame
        try {
            // generate an exception to use below
            throw new Exception();
        } catch (Exception $e) {
            $context = ContextAPI::buildContextFromException($e);
            $callback($context, $e);
        }
    }



    /**
     * Test that the file and line numbers are shifted by 1 frame when building the callstack.
     *
     * @test
     *
     * @return void
     * @throws Exception Doesn't throw this, but phpcs expects this to be here.
     */
    public static function test_that_the_callstack_file_and_line_numbers_are_shifted_by_1(): void
    {
        $closure = function () {

            $context = Clarity::buildContextHere();

            // find the two frames from within this file
            $frames = [];
            /** @var Frame $frame */
            foreach ($context->getCallStack() as $frame) {
                if ($frame->getFile() == __FILE__) {
                    $frames[] = $frame;
                }
            }

            $frame = $frames[0];
            self::assertSame(
                'test_that_the_callstack_file_and_line_numbers_are_shifted_by_1',
                $frame->getFunction()
            );
            self::assertSame(__FILE__, $frame->getFile());
            self::assertSame(__LINE__ + 11, $frame->getLine());

            $frame = $frames[1];
            self::assertSame(
                'CodeDistortion\ClarityContext\Tests\Integration\Support\Context\CallStack\{closure}',
                $frame->getFunction()
            );
            self::assertSame(__FILE__, $frame->getFile());
            self::assertSame(__LINE__ - 25, $frame->getLine());
        };

        $closure();
    }



    /**
     * Test the last frame is marked as the last frame.
     *
     * @test
     *
     * @return void
     */
    public static function test_that_the_last_frame_is_marked_so(): void
    {
        // simulate Control running and catching an exception
        $catcherObjectId = 1;
        $e = SimulateControlPackage::pushControlCallMetaAndGenerateException($catcherObjectId);
        $context = SimulateControlPackage::buildContext($catcherObjectId, $e);

        $count = 0;
        foreach ($context->getStackTrace() as $frame) {
            $count++ == 0
                ? self::assertTrue($frame->isLastFrame())
                : self::assertFalse($frame->isLastFrame());
        }
    }





    /**
     * Build the exception's callstack. Include the exception's location as a frame.
     *
     * @param Throwable $e The exception to use.
     * @return array<integer, mixed[]>
     */
    private static function getExceptionCallStackArray(Throwable $e): array
    {
        $exceptionCallStack = array_reverse($e->getTrace());

        // add the exception's location as the last frame
        $exceptionCallStack[] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        return $exceptionCallStack;
    }
}
