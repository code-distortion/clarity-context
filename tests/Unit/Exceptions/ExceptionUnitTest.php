<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Exceptions;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextInitialisationException;
use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the Exception classes.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ExceptionUnitTest extends PHPUnitTestCase
{
    /**
     * Test the messages that exceptions generate.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_exception_messages(): void
    {
        // ClarityContextInitialisationException

        self::assertSame(
            'The current framework type could not be resolved',
            ClarityContextInitialisationException::unknownFramework()->getMessage()
        );

        self::assertSame(
            'Level "blah" is not allowed. '
            . 'Please choose from: debug, info, notice, warning, error, critical, alert, emergency',
            ClarityContextInitialisationException::levelNotAllowed('blah')->getMessage()
        );

        self::assertSame(
            'Invalid meta type "invalid"',
            ClarityContextInitialisationException::invalidMetaType('invalid')->getMessage()
        );



        // ClarityContextRuntimeException

        self::assertSame(
            'Invalid number of frames to go back: -1',
            ClarityContextRuntimeException::invalidFramesBack(-1)->getMessage()
        );

        self::assertSame(
            "Can't go back that many frames: 5 (current number of frames: 4, there must be at least one left)",
            ClarityContextRuntimeException::tooManyFramesBack(5, 4)->getMessage()
        );
    }
}
