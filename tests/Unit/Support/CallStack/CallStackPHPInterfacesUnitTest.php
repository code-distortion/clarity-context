<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\CallStack;

use CodeDistortion\ClarityContext\Support\CallStack\CallStack;
use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\Support;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use InvalidArgumentException;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test CallStack's implementation of PHP's ArrayAccess, Countable and SeekableIterator Interfaces.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CallStackPHPInterfacesUnitTest extends PHPUnitTestCase
{
    /**
     * Test CallStack's implementation of PHP's ArrayAccess, Countable and SeekableIterator Interfaces.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_call_stack(): void
    {
        $implements = class_implements(CallStack::class);
        self::assertArrayHasKey('ArrayAccess', $implements);
        self::assertArrayHasKey('Countable', $implements);
        self::assertArrayHasKey('SeekableIterator', $implements);

        // build a CallStack object
        $frame1 = self::buildCallStackFrame();
        $frame2 = self::buildCallStackFrame();
        $frame3 = self::buildCallStackFrame();
        $frame4 = self::buildCallStackFrame();

        $callStack = new CallStack([$frame1, $frame2, $frame3]);



        // Countable
        self::assertSame(3, $callStack->count());
        self::assertSame(3, count($callStack));
        self::assertCount(3, $callStack);



        // ArrayAccess
        self::assertSame($frame1, $callStack[0]);
        self::assertSame($frame2, $callStack[1]);
        self::assertSame($frame3, $callStack[2]);
        self::assertFalse(isset($callStack['blah']));
        self::assertFalse(isset($callStack[-1]));
        self::assertTrue(isset($callStack[0]));
        self::assertTrue(isset($callStack[1]));
        self::assertTrue(isset($callStack[2]));
        self::assertFalse(isset($callStack[3]));
        self::assertCount(3, $callStack);

        $callStack[3] = $frame4;
        self::assertSame($frame4, $callStack[3]);
        self::assertTrue(isset($callStack[3]));
        self::assertCount(4, $callStack);

        unset($callStack[3]);
        self::assertFalse(isset($callStack[4]));
        self::assertCount(3, $callStack);

        $exceptionOccurred = false;
        try {
            $callStack[0] = "Something that's not a Frame object";
        } catch (InvalidArgumentException) {
            $exceptionOccurred = true;
        }
        self::assertTrue($exceptionOccurred);



        // SeekableIterator

        // loop through with foreach loop
        $count = 0;
        foreach ($callStack as $frame) {
            if ($count == 0) {
                self::assertSame($frame1, $frame);
            } elseif ($count == 1) {
                self::assertSame($frame2, $frame);
            } elseif ($count == 2) {
                self::assertSame($frame3, $frame);
            }
            $count++;
        }

        // loop through manually
        $callStack->rewind();
        self::assertSame(0, $callStack->key());
        self::assertSame($frame1, $callStack->current());
        $callStack->next();
        self::assertSame(1, $callStack->key());
        self::assertSame($frame2, $callStack->current());
        $callStack->next();
        self::assertSame(2, $callStack->key());
        self::assertSame($frame3, $callStack->current());
        $callStack->next();
        self::assertSame(3, $callStack->key());
        self::assertSame(null, $callStack->current());

        // seek
        $callStack->seek(1);
        self::assertSame($frame2, $callStack->current());

        $threwException = false;
        try {
            $callStack->seek(-1);
        } catch (OutOfBoundsException) {
            $threwException = true;
        }
        self::assertTrue($threwException);

        // reverse
        $callStack->seek(1);
        self::assertSame($frame2, $callStack->current());
        $callStack->reverse(); // will reset back to position 0, after reversing
        self::assertSame($frame3, $callStack->current());
        $count = 0;
        foreach ($callStack as $frame) {
            if ($count == 0) {
                self::assertSame($frame3, $frame);
            } elseif ($count == 1) {
                self::assertSame($frame2, $frame);
            } elseif ($count == 2) {
                self::assertSame($frame1, $frame);
            }
            $count++;
        }



        // test that keys aren't preserved
        $callStack = new CallStack(['a' => $frame1, 'b' => $frame2, 'c' => $frame3]);
        self::assertTrue(isset($callStack[0]));
    }



    /**
     * Build a dummy CallStackFrame object.
     *
     * @return Frame
     */
    private static function buildCallStackFrame(): Frame
    {
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
            [],
            $isApplicationFrame,
            false,
            false,
            false,
            false,
        );
    }
}
