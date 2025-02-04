<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\CallStack;

use CodeDistortion\ClarityContext\Support\CallStack\CallStack;
use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\Support;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test CallStack's methods that search for different types of frames.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CallStackFramesUnitTest extends PHPUnitTestCase
{
    /**
     * Test that CallStack can find the last application frame properly.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_accessing_the_last_application_frame(): void
    {
        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', false);
        $frame2 = self::buildCallStackFrame('/var/www/html/src/some-file2', '/var/www/html', false);
        $frame3 = self::buildCallStackFrame('/var/www/html/src/some-file3', '/var/www/html', true);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(2, $callStack->getLastApplicationFrameIndex());
        self::assertSame($frame3, $callStack->getLastApplicationFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', false);
        $frame2 = self::buildCallStackFrame('/var/www/html/src/some-file2', '/var/www/html', true);
        $frame3 = self::buildCallStackFrame('/var/www/html/vendor/some-file3', '/var/www/html', false);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(1, $callStack->getLastApplicationFrameIndex());
        self::assertSame($frame2, $callStack->getLastApplicationFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', true);
        $frame2 = self::buildCallStackFrame('/var/www/html/vendor/some-file2', '/var/www/html', false);
        $frame3 = self::buildCallStackFrame('/var/www/html/vendor/some-file3', '/var/www/html', false);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(0, $callStack->getLastApplicationFrameIndex());
        self::assertSame($frame1, $callStack->getLastApplicationFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', false);
        $frame2 = self::buildCallStackFrame('/var/www/html/vendor/some-file2', '/var/www/html', false);
        $frame3 = self::buildCallStackFrame('/var/www/html/src/some-file3', '/var/www/html', true);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(2, $callStack->getLastApplicationFrameIndex());
        self::assertSame($frame3, $callStack->getLastApplicationFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/vendor/some-file1', '/var/www/html', false);
        $frame2 = self::buildCallStackFrame('/var/www/html/vendor/some-file2', '/var/www/html', false);
        $frame3 = self::buildCallStackFrame('/var/www/html/vendor/some-file3', '/var/www/html', false);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(null, $callStack->getLastApplicationFrameIndex());
        self::assertSame(null, $callStack->getLastApplicationFrame());
    }



    /**
     * Test that CallStack can find the "exception thrown" frame properly.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_accessing_the_thrown_here_frame(): void
    {
        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', false, false);
        $frame2 = self::buildCallStackFrame('/var/www/html/src/some-file2', '/var/www/html', false, false);
        $frame3 = self::buildCallStackFrame('/var/www/html/src/some-file3', '/var/www/html', true, true);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(2, $callStack->getExceptionThrownFrameIndex());
        self::assertSame($frame3, $callStack->getExceptionThrownFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', false, false);
        $frame2 = self::buildCallStackFrame('/var/www/html/src/some-file2', '/var/www/html', true, true);
        $frame3 = self::buildCallStackFrame('/var/www/html/vendor/some-file3', '/var/www/html', false, false);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(1, $callStack->getExceptionThrownFrameIndex());
        self::assertSame($frame2, $callStack->getExceptionThrownFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', true, true);
        $frame2 = self::buildCallStackFrame('/var/www/html/vendor/some-file2', '/var/www/html', false, false);
        $frame3 = self::buildCallStackFrame('/var/www/html/vendor/some-file3', '/var/www/html', false, false);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(0, $callStack->getExceptionThrownFrameIndex());
        self::assertSame($frame1, $callStack->getExceptionThrownFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', false, false);
        $frame2 = self::buildCallStackFrame('/var/www/html/vendor/some-file2', '/var/www/html', false, false);
        $frame3 = self::buildCallStackFrame('/var/www/html/src/some-file3', '/var/www/html', true, true);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(2, $callStack->getExceptionThrownFrameIndex());
        self::assertSame($frame3, $callStack->getExceptionThrownFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/vendor/some-file1', '/var/www/html', false, false);
        $frame2 = self::buildCallStackFrame('/var/www/html/vendor/some-file2', '/var/www/html', false, false);
        $frame3 = self::buildCallStackFrame('/var/www/html/vendor/some-file3', '/var/www/html', false, false);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(null, $callStack->getExceptionThrownFrameIndex());
        self::assertSame(null, $callStack->getExceptionThrownFrame());
    }



    /**
     * Test that CallStack can find the "exception caught" frame properly.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_accessing_the_caught_here_frame(): void
    {
        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', false, false, false);
        $frame2 = self::buildCallStackFrame('/var/www/html/src/some-file2', '/var/www/html', false, false, false);
        $frame3 = self::buildCallStackFrame('/var/www/html/src/some-file3', '/var/www/html', true, true, true);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(2, $callStack->getExceptionCaughtFrameIndex());
        self::assertSame($frame3, $callStack->getExceptionCaughtFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', false, false, false);
        $frame2 = self::buildCallStackFrame('/var/www/html/src/some-file2', '/var/www/html', true, true, true);
        $frame3 = self::buildCallStackFrame('/var/www/html/vendor/some-file3', '/var/www/html', false, false, false);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(1, $callStack->getExceptionCaughtFrameIndex());
        self::assertSame($frame2, $callStack->getExceptionCaughtFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', true, true, true);
        $frame2 = self::buildCallStackFrame('/var/www/html/vendor/some-file2', '/var/www/html', false, false, false);
        $frame3 = self::buildCallStackFrame('/var/www/html/vendor/some-file3', '/var/www/html', false, false, false);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(0, $callStack->getExceptionCaughtFrameIndex());
        self::assertSame($frame1, $callStack->getExceptionCaughtFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/src/some-file1', '/var/www/html', false, false, false);
        $frame2 = self::buildCallStackFrame('/var/www/html/vendor/some-file2', '/var/www/html', false, false, false);
        $frame3 = self::buildCallStackFrame('/var/www/html/src/some-file3', '/var/www/html', true, true, true);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(2, $callStack->getExceptionCaughtFrameIndex());
        self::assertSame($frame3, $callStack->getExceptionCaughtFrame());

        $frame1 = self::buildCallStackFrame('/var/www/html/vendor/some-file1', '/var/www/html', false, false, false);
        $frame2 = self::buildCallStackFrame('/var/www/html/vendor/some-file2', '/var/www/html', false, false, false);
        $frame3 = self::buildCallStackFrame('/var/www/html/vendor/some-file3', '/var/www/html', false, false, false);
        $callStack = new CallStack([$frame1, $frame2, $frame3]);
        self::assertSame(null, $callStack->getExceptionCaughtFrameIndex());
        self::assertSame(null, $callStack->getExceptionCaughtFrame());
    }



    /**
     * Build a dummy CallStackFrame object.
     *
     * @param string  $file                   The file to use.
     * @param string  $projectRootDir         The project-root-dir to use.
     * @param boolean $isLastApplicationFrame Whether this is the last application frame or not.
     * @param boolean $thrownHere             Whether the exception was thrown in this frame or not.
     * @param boolean $caughtHere             Whether the exception was caught in this frame or not.
     * @return Frame
     */
    private static function buildCallStackFrame(
        string $file = 'some-file',
        string $projectRootDir = '',
        bool $isLastApplicationFrame = false,
        bool $thrownHere = false,
        bool $caughtHere = false,
    ): Frame {

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
            [],
            $isApplicationFrame,
            $isLastApplicationFrame,
            false,
            $thrownHere,
            $caughtHere,
        );
    }
}
