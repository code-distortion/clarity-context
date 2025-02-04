<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\CallStack;

use CodeDistortion\ClarityContext\Support\CallStack\CallStack;
use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\CallMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionCaughtMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionThrownMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\LastApplicationFrameMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\Meta;
use CodeDistortion\ClarityContext\Support\Support;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test that CallStack returns Meta objects properly.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CallStackMetaUnitTest extends PHPUnitTestCase
{
    /**
     * Test that CallStack returns Meta objects properly.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_retrieval_of_meta_objects(): void
    {
        $callMeta1 = self::buildCallMeta();

        $contextMeta1 = self::buildContextMetaContainingSentence();
        $contextMeta2 = self::buildContextMetaContainingSentence();

        $exceptionThrownMeta = self::buildExceptionThrownMeta();

        $exceptionCaughtMeta = self::buildExceptionCaughtMeta();

        $lastApplicationFrameMeta = self::buildLastApplicationFrameMeta();



        # no Frames
        $callStack = new CallStack([]);
        self::assertSame([], $callStack->getMeta());



        # Frames with no Meta objects
        $frame1 = self::buildCallStackFrame([]);
        $frame2 = self::buildCallStackFrame([]);
        $frame3 = self::buildCallStackFrame([]);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame([], $callStack->getMeta());



        # Frames with a Meta object
        $frame1 = self::buildCallStackFrame([$contextMeta1]);
        $frame2 = self::buildCallStackFrame([]);
        $frame3 = self::buildCallStackFrame([]);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame([$contextMeta1], $callStack->getMeta());



        # Frames with a Meta objects
        $frame1 = self::buildCallStackFrame([$contextMeta1, $contextMeta2]);
        $frame2 = self::buildCallStackFrame([]);
        $frame3 = self::buildCallStackFrame([]);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame([$contextMeta1, $contextMeta2], $callStack->getMeta());

        # Frames with a Meta objects
        $frame1 = self::buildCallStackFrame([$contextMeta1]);
        $frame2 = self::buildCallStackFrame([]);
        $frame3 = self::buildCallStackFrame([$contextMeta2]);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame([$contextMeta1, $contextMeta2], $callStack->getMeta());



        # Frames with a Meta objects - but request a type that doesn't exist
        $frame1 = self::buildCallStackFrame([$contextMeta1, $callMeta1]);
        $frame2 = self::buildCallStackFrame([$exceptionCaughtMeta]);
        $frame3 = self::buildCallStackFrame([$contextMeta2, $exceptionThrownMeta]);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame([], $callStack->getMeta(LastApplicationFrameMeta::class));

        # Frames with a Meta objects - and request a type that does exist
        $frame1 = self::buildCallStackFrame([$contextMeta1, $callMeta1, $lastApplicationFrameMeta]);
        $frame2 = self::buildCallStackFrame([$exceptionCaughtMeta]);
        $frame3 = self::buildCallStackFrame([$contextMeta2, $exceptionThrownMeta]);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame([$lastApplicationFrameMeta], $callStack->getMeta(LastApplicationFrameMeta::class));
        self::assertSame([$contextMeta1, $contextMeta2], $callStack->getMeta(ContextMeta::class));
    }



    /**
     * Build a dummy CallMeta object.
     *
     * @return CallMeta
     */
    private static function buildCallMeta(): CallMeta
    {
        $frameData = [
            'file' => 'somewhere',
            'line' => 123,
        ];

        return new CallMeta($frameData, 'somewhere', false, []);
    }

    /**
     * Build a dummy ContextMeta object - containing a sentence.
     *
     * @return ContextMeta
     */
    private static function buildContextMetaContainingSentence(): ContextMeta
    {
        $frameData = [
            'file' => 'somewhere',
            'line' => 123,
        ];

        return new ContextMeta($frameData, 'somewhere', 'something');
    }

    /**
     * Build a dummy ExceptionCaughtMeta object.
     *
     * @return ExceptionCaughtMeta
     */
    private static function buildExceptionCaughtMeta(): ExceptionCaughtMeta
    {
        $frameData = [
            'file' => 'somewhere',
            'line' => 123,
        ];

        return new ExceptionCaughtMeta($frameData, 'somewhere');
    }

    /**
     * Build a dummy ExceptionCaughtMeta object.
     *
     * @return ExceptionThrownMeta
     */
    private static function buildExceptionThrownMeta(): ExceptionThrownMeta
    {
        $frameData = [
            'file' => 'somewhere',
            'line' => 123,
        ];

        return new ExceptionThrownMeta($frameData, 'somewhere');
    }

    /**
     * Build a dummy ExceptionCaughtMeta object.
     *
     * @return LastApplicationFrameMeta
     */
    private static function buildLastApplicationFrameMeta(): LastApplicationFrameMeta
    {
        $frameData = [
            'file' => 'somewhere',
            'line' => 123,
        ];

        return new LastApplicationFrameMeta($frameData, 'somewhere');
    }



    /**
     * Build a dummy CallStackFrame object.
     *
     * @param Meta[] $metaObjects The meta-objects the frame will have.
     * @return Frame
     */
    private static function buildCallStackFrame(
        array $metaObjects,
    ): Frame {

        $file = 'some-file';
        $projectRootDir = '';

        $file = (string) str_replace('/', DIRECTORY_SEPARATOR, $file);
        $projectRootDir = str_replace('/', DIRECTORY_SEPARATOR, $projectRootDir);

        $projectFile = Support::resolveProjectFile($file, $projectRootDir);
        $isApplicationFrame = Support::isApplicationFile($projectFile, $projectRootDir);

        return new Frame(
            [
                'file' => $file,
                'line' => mt_rand(),
            ],
            $projectFile,
            $metaObjects,
            $isApplicationFrame,
            false,
            false,
            false,
            false,
        );
    }
}
