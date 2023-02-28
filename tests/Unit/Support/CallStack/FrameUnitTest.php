<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\CallStack;

use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\Meta;
use CodeDistortion\ClarityContext\Support\Support;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use stdClass;

/**
 * Test the CallStackFrame class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class FrameUnitTest extends PHPUnitTestCase
{
    /**
     * Test the CallStackFrame class.
     *
     * @test
     *
     * @return void
     */
    public static function test_call_stack_frame(): void
    {
        $projectRootDir = (string) realpath(__DIR__ . '/../../../../');

        self::buildAndTestCallStackFrame(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            [],
            false,
            false,
            false,
            false,
            false,
            '',
        );

        self::buildAndTestCallStackFrame(
            __FILE__,
            null,
            null,
            null,
            null,
            null,
            null,
            [],
            false,
            false,
            false,
            false,
            false,
            '',
        );

        self::buildAndTestCallStackFrame(
            '/somewhere-else',
            null,
            null,
            null,
            null,
            null,
            null,
            [],
            false,
            false,
            false,
            false,
            false,
            $projectRootDir,
        );

        foreach ([true, false] as $thrownHere) {
            foreach ([true, false] as $caughtHere) {
                foreach ([true, false] as $isApplicationFrame) {
                    foreach ([true, false] as $isLastApplicationFrame) {
                        foreach ([true, false] as $isLastFrame) {

                            self::buildAndTestCallStackFrame(
                                __FILE__,
                                123,
                                'someFunc',
                                'someClass',
                                new stdClass(),
                                'someType',
                                [1, 2],
                                [self::buildContextMetaContainingSentence()],
                                $thrownHere,
                                $caughtHere,
                                $isApplicationFrame,
                                $isLastApplicationFrame,
                                $isLastFrame,
                                $projectRootDir,
                            );
                        }
                    }
                }
            }
        }

        self::buildAndTestCallStackFrame(
            '/somewhere-else',
            null,
            null,
            null,
            null,
            null,
            null,
            [self::buildContextMetaContainingSentence(), self::buildContextMetaContainingArray()],
            false,
            false,
            false,
            false,
            false,
            $projectRootDir,
        );
    }

    /**
     * Build and test a CallStackFrame object.
     *
     * @param string|null  $file                   The file to use.
     * @param integer|null $line                   The line number to use.
     * @param string|null  $function               The function to use.
     * @param string|null  $class                  The class to use.
     * @param object|null  $object                 The object to use.
     * @param string|null  $type                   The type to use.
     * @param mixed[]|null $args                   The args to use.
     * @param Meta[]       $meta                   The meta objects to use.
     * @param boolean      $thrownHere             Whether the exception was thrown by this frame or not.
     * @param boolean      $caughtHere             Whether the exception was caught by this frame or not.
     * @param boolean      $isApplicationFrame     Whether this is an application frame or not.
     * @param boolean      $isLastApplicationFrame Whether this is the last application frame or not.
     * @param boolean      $isLastFrame            Whether this is the last frame or not.
     * @param string       $projectRootDir         The project-root-dir to use.
     * @return void
     */
    private static function buildAndTestCallStackFrame(
        ?string $file,
        ?int $line,
        ?string $function,
        ?string $class,
        ?object $object,
        ?string $type,
        ?array $args,
        array $meta,
        bool $thrownHere,
        bool $caughtHere,
        bool $isApplicationFrame,
        bool $isLastApplicationFrame,
        bool $isLastFrame,
        string $projectRootDir
    ): void {

        $projectFile = Support::resolveProjectFile((string) $file, $projectRootDir);

        $frameData = [
            'file' => $file,
            'line' => $line,
            'function' => $function,
            'class' => $class,
            'object' => $object,
            'type' => $type,
            'args' => $args,
        ];

        $frame = new Frame(
            $frameData,
            $projectFile,
            $meta,
            $isApplicationFrame,
            $isLastApplicationFrame,
            $isLastFrame,
            $thrownHere,
            $caughtHere,
        );

        $contextMetas = [];
        foreach ($meta as $oneMeta) {
            if ($oneMeta instanceof ContextMeta) {
                $contextMetas[] = $oneMeta;
            }
        }

        self::assertSame((string) $file, $frame->getFile());
        self::assertSame($projectFile, $frame->getProjectFile());
        self::assertSame((int) $line, $frame->getLine());
        self::assertSame((string) $function, $frame->getFunction());
        self::assertSame((string) $class, $frame->getClass());
        self::assertSame($object, $frame->getObject());
        self::assertSame((string) $type, $frame->getType());
        self::assertSame($args, $frame->getArgs());
        self::assertSame($meta, $frame->getMeta());
        self::assertSame($contextMetas, $frame->getMeta(ContextMeta::class));
        self::assertSame($contextMetas, $frame->getMeta([ContextMeta::class]));
        // check that the ContextMetas don't get doubled up
        self::assertSame($meta, $frame->getMeta([ContextMeta::class, Meta::class]));
        self::assertSame($isApplicationFrame, $frame->isApplicationFrame());
        self::assertSame($isLastApplicationFrame, $frame->isLastApplicationFrame());
        self::assertSame(!$isApplicationFrame, $frame->isVendorFrame());
        self::assertSame($isLastFrame, $frame->isLastFrame());
        self::assertSame($thrownHere, $frame->exceptionWasThrownHere());
        self::assertSame($caughtHere, $frame->exceptionWasCaughtHere());

        self::_testBuildCopyWithExtraMeta($frame, false, false);
        self::_testBuildCopyWithExtraMeta($frame, false, true);
        self::_testBuildCopyWithExtraMeta($frame, true, false);
        self::_testBuildCopyWithExtraMeta($frame, true, true);

        self::assertSame($frameData, $frame->getRawFrameData());
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
     * Build a dummy ContextMeta object - containing an array.
     *
     * @return ContextMeta
     */
    private static function buildContextMetaContainingArray(): ContextMeta
    {
        $frameData = [
            'file' => 'somewhere',
            'line' => 123,
        ];

        return new ContextMeta($frameData, 'somewhere', ['a' => 'b']);
    }



    /**
     * Test the buildCopyWithExtraMeta method.
     *
     * @param Frame   $frame                  The frame to copy.
     * @param boolean $thrownHere             Whether the exception was thrown by this frame or not.
     * @param boolean $isLastApplicationFrame Whether this is the last application frame or not.
     * @return void
     */
    private static function _testBuildCopyWithExtraMeta(
        Frame $frame,
        bool $thrownHere,
        bool $isLastApplicationFrame
    ): void {

        $contextMeta = self::buildContextMetaContainingSentence();
        $frame2 = $frame->buildCopyWithExtraMeta($contextMeta, $thrownHere, $isLastApplicationFrame);

        self::assertSame($frame->getFile(), $frame2->getFile());
        self::assertSame($frame->getProjectFile(), $frame2->getProjectFile());
        self::assertSame($frame->getLine(), $frame2->getLine());
        self::assertSame($frame->getFunction(), $frame2->getFunction());
        self::assertSame($frame->getClass(), $frame2->getClass());
        self::assertSame($frame->getObject(), $frame2->getObject());
        self::assertSame($frame->getType(), $frame2->getType());
        self::assertSame($frame->getArgs(), $frame2->getArgs());

        $contextMetas = array_merge($frame->getMeta(), [$contextMeta]);
        self::assertSame($contextMetas, $frame2->getMeta());
        self::assertSame($contextMetas, $frame2->getMeta(ContextMeta::class));
        self::assertSame($contextMetas, $frame2->getMeta([ContextMeta::class]));
        self::assertSame($frame->isApplicationFrame(), $frame2->isApplicationFrame());
        $newIsLastApplicationFrame = $frame->isLastApplicationFrame() || $isLastApplicationFrame;
        self::assertSame($newIsLastApplicationFrame, $frame2->isLastApplicationFrame());
        self::assertSame($frame->isVendorFrame(), $frame2->isVendorFrame());
        self::assertSame($frame->isLastFrame(), $frame2->isLastFrame());
        self::assertSame($frame->exceptionWasThrownHere() || $thrownHere, $frame2->exceptionWasThrownHere());
        self::assertSame($frame->exceptionWasCaughtHere(), $frame2->exceptionWasCaughtHere());
    }
}
