<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionThrownMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\LastApplicationFrameMeta;
use CodeDistortion\ClarityContext\Support\MetaCallStack;
use CodeDistortion\ClarityContext\Support\Support;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the Support class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class SupportUnitTest extends PHPUnitTestCase
{
    /**
     * Test that arguments get normalised properly.
     *
     * @test
     * @dataProvider argumentDataProvider
     *
     * @param mixed[]        $previous The "previous" arguments.
     * @param array<mixed[]> $args     The "new" arguments to add.
     * @param mixed[]        $expected The expected output.
     * @return void
     */
    #[Test]
    #[DataProvider('argumentDataProvider')]
    public static function test_normalise_args_method(array $previous, array $args, array $expected): void
    {
        $normalised = Support::normaliseArgs($previous, $args);
        self::assertSame($expected, $normalised);
    }

    /**
     * DataProvider for test_that_arguments_are_normalised().
     *
     * @return array<array<string, mixed>>
     */
    public static function argumentDataProvider(): array
    {
        $value1 = 'a';
        $value2 = 'b';
        $value3 = 'c';
        $value4 = 'd';

        $array1 = [$value1];
        $array2 = [$value2];
        $array3 = [$value3];
        $array4 = [$value4];

        $object1 = (object) [$value1 => $value1];
        $object2 = (object) [$value2 => $value2];
        $object3 = (object) [$value3 => $value3];
        $object4 = (object) [$value4 => $value4];

        return [
            ...self::buildSetOfArgs($value1, $value2, $value3, $value4),
            ...self::buildSetOfArgs($array1, $array2, $array3, $array4),
            ...self::buildSetOfArgs($object1, $object2, $object3, $object4),
        ];
    }

    /**
     * Build combinations of inputs to test.
     *
     * @param mixed $one   Value 1.
     * @param mixed $two   Value 2.
     * @param mixed $three Value 3.
     * @param mixed $four  Value 4.
     * @return array<array<string, mixed>>
     */
    private static function buildSetOfArgs(mixed $one, mixed $two, mixed $three, mixed $four): array
    {
        return [
            self::buildArgs([], []),
            self::buildArgs([$one, $two], []),
            self::buildArgs([], [$one, $two]),
            self::buildArgs([$one, $two], [$three, $four]),
            self::buildArgs([$one, $two], [$two, $three]),
            self::buildArgs([$one, $one], []),
            self::buildArgs([], [$one, $one]),
            self::buildArgs([null], [$one, $two]),
            self::buildArgs([$one, $two], [null]),
        ];
    }

    /**
     * @param mixed[]               $previous The "previous" arguments.
     * @param array<integer, mixed> $args     The "new" arguments to add.
     * @return array<string, mixed>
     */
    private static function buildArgs(array $previous, array $args): array
    {
        foreach ($args as $arg) {
            $arg = is_array($arg)
                ? $arg
                : [$arg];
            $previous = array_merge($previous, $arg);
        }

        $expected = array_values(
            array_unique(
                array_filter($previous),
                SORT_REGULAR
            )
        );

        return [
            'previous' => $previous,
            'args' => $args,
            'expected' => $expected,
        ];
    }



    /**
     * Test Support::resolveProjectFile to see that it generates the correct project file.
     *
     * @test
     * @dataProvider resolveProjectFileDataProvider
     *
     * @param string $expected       The expected "project file".
     * @param string $file           The input file.
     * @param string $projectRootDir The project root dir.
     * @return void
     */
    #[Test]
    #[DataProvider('resolveProjectFileDataProvider')]
    public static function test_resolve_project_file_method(
        string $expected,
        string $file,
        string $projectRootDir
    ): void {

        $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $projectRootDir = str_replace('/', DIRECTORY_SEPARATOR, $projectRootDir);

        self::assertSame($expected, Support::resolveProjectFile($file, $projectRootDir));
    }

    /**
     * DataProvider for test_resolve_project_file().
     *
     * @return array<array<string, string>>
     */
    public static function resolveProjectFileDataProvider(): array
    {
        $return = [];

        // no projectRootDir - the root couldn't be resolved, so it's impossible to pick the project-path
        $return[] = [
            'expected' => '/path/to/root/src/file.php',
            'file' => '/path/to/root/src/file.php',
            'projectRootDir' => ''
        ];
        $return[] = [
            'expected' => '/path/to/root-other/src/file.php',
            'file' => '/path/to/root-other/src/file.php',
            'projectRootDir' => ''
        ];
        $return[] = [
            'expected' => '/not/path/to/root/src/file.php',
            'file' => '/not/path/to/root/src/file.php',
            'projectRootDir' => ''
        ];

        // with projectRootDir - the root was resolved, so the project-path can be picked
        $return[] = [
            'expected' => '/src/file.php',
            'file' => '/path/to/root/src/file.php',
            'projectRootDir' => '/path/to/root'
        ];
        $return[] = [
            'expected' => '/path/to/root-other/src/file.php',
            'file' => '/path/to/root-other/src/file.php',
            'projectRootDir' => '/path/to/root'
        ];
        $return[] = [
            'expected' => '/not/path/to/root/src/file.php',
            'file' => '/not/path/to/root/src/file.php',
            'projectRootDir' => '/path/to/root'
        ];

        // with projectRootDir - that has a trailing "/"
        $return[] = [
            'expected' => '/src/file.php',
            'file' => '/path/to/root/src/file.php',
            'projectRootDir' => '/path/to/root/'
        ];
        $return[] = [
            'expected' => '/path/to/root-other/src/file.php',
            'file' => '/path/to/root-other/src/file.php',
            'projectRootDir' => '/path/to/root/'
        ];
        $return[] = [
            'expected' => '/not/path/to/root/src/file.php',
            'file' => '/not/path/to/root/src/file.php',
            'projectRootDir' => '/path/to/root/'
        ];

        // with non-ascii characters
        $return[] = [
            'expected' => '/src/file.php',
            'file' => '/path/to/ro☺️ot/src/file.php',
            'projectRootDir' => '/path/to/ro☺️ot/'
        ];
        $return[] = [
            'expected' => '/src/fi☺️le.php',
            'file' => '/path/to/root/src/fi☺️le.php',
            'projectRootDir' => '/path/to/root/'
        ];

        return $return;
    }



    /**
     * Test Support::isApplicationFile to see that it checks if a project file is an application file correctly.
     *
     * @test
     * @dataProvider resolveIsApplicationFileCheckDataProvider
     *
     * @param boolean $expected       The expected outcome.
     * @param string  $projectFile    The expected "project file".
     * @param string  $projectRootDir The project root dir.
     * @return void
     */
    #[Test]
    #[DataProvider('resolveIsApplicationFileCheckDataProvider')]
    public static function test_is_application_file_check_method(
        bool $expected,
        string $projectFile,
        string $projectRootDir
    ): void {

        $projectFile = str_replace('/', DIRECTORY_SEPARATOR, $projectFile);
        $projectRootDir = str_replace('/', DIRECTORY_SEPARATOR, $projectRootDir);

        self::assertSame($expected, Support::isApplicationFile($projectFile, $projectRootDir));
    }

    /**
     * DataProvider for test_resolve_project_file().
     *
     * @return array<array<string, string|boolean>>
     */
    public static function resolveIsApplicationFileCheckDataProvider(): array
    {
        $return = [];

        // no projectRootDir - the root couldn't be resolved, so it's impossible to tell if it's a vendor file
        $return[] = [
            'expected' => true,
            'projectFile' => '/src/file.php',
            'projectRootDir' => '',
        ];
        $return[] = [
            'expected' => true,
            'projectFile' => '/vendor/file.php',
            'projectRootDir' => '',
        ];
        $return[] = [
            'expected' => true,
            'projectFile' => 'README.md',
            'projectRootDir' => '',
        ];

        // with projectRootDir - the root was resolved, so the check can proceed
        $return[] = [
            'expected' => true,
            'projectFile' => '/src/file.php',
            'projectRootDir' => '/path/to/root/',
        ];
        $return[] = [
            'expected' => false,
            'projectFile' => '/vendor/file.php',
            'projectRootDir' => '/path/to/root/',
        ];
        $return[] = [
            'expected' => true,
            'projectFile' => 'README.md',
            'projectRootDir' => '/path/to/root/',
        ];

        return $return;
    }



    /**
     * Test decideIfMetaCountsAreWorthListing() to see that it decides if the Meta counts are worth listing.
     *
     * @test
     * @dataProvider metaTypeCountDataProvider
     *
     * @param array<string,integer> $metaTypeCounts The counts of the Meta objects.
     * @param boolean               $expected       The expected outcome.
     * @return void
     */
    #[Test]
    #[DataProvider('metaTypeCountDataProvider')]
    public static function test_decide_if_meta_counts_are_worth_listing_method(
        array $metaTypeCounts,
        bool $expected
    ): void {

        self::assertSame($expected, Support::decideIfMetaCountsAreWorthListing($metaTypeCounts));
    }

    /**
     * DataProvider for test_decide_if_meta_counts_are_worth_listing_method().
     *
     * @return array<array<array<string,integer>|boolean>>
     */
    public static function metaTypeCountDataProvider(): array
    {
        $return = [];

        $return[] = [[], false];
        $return[] = [[ContextMeta::class => 1], true];
        $return[] = [[LastApplicationFrameMeta::class => 1], false];
        $return[] = [[ExceptionThrownMeta::class => 1], false];
        $return[] = [[ExceptionThrownMeta::class => 1, LastApplicationFrameMeta::class => 1], false];
        $return[] = [
            [ExceptionThrownMeta::class => 1, LastApplicationFrameMeta::class => 1, ContextMeta::class => 1],
            true
        ];

        return $return;
    }



    /**
     * Test that the global meta call stack can be fetched, and is the same instance each time.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_the_get_global_meta_call_stack_method(): void
    {
        $metaCallStack1 = Support::getGlobalMetaCallStack();
        $metaCallStack2 = Support::getGlobalMetaCallStack();

        self::assertInstanceOf(MetaCallStack::class, $metaCallStack1);
        self::assertSame($metaCallStack1, $metaCallStack2);
    }



    /**
     * Test the method that removes frames from a stack trace.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_step_back_stack_trace_method(): void
    {
        $phpStackTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);

        // remove no frames
        $newStackTrace = Support::stepBackStackTrace($phpStackTrace, 0);
        self::assertSame($phpStackTrace, $newStackTrace);

        // remove 1 frame
        $newStackTrace = Support::stepBackStackTrace($phpStackTrace, 1);
        $tempStackTrace = $phpStackTrace;
        array_shift($tempStackTrace);
        self::assertSame($tempStackTrace, $newStackTrace);

        // remove 2 frames
        $newStackTrace = Support::stepBackStackTrace($phpStackTrace, 2);
        $tempStackTrace = $phpStackTrace;
        array_shift($tempStackTrace);
        array_shift($tempStackTrace);
        self::assertSame($tempStackTrace, $newStackTrace);

        // remove too many frames
        $caughtException = false;
        try {
            // generate an exception
            Support::stepBackStackTrace($phpStackTrace, count($phpStackTrace));
        } catch (ClarityContextRuntimeException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);

        // test an invalid number of steps to go back
        $caughtException = false;
        try {
            // generate an exception
            Support::stepBackStackTrace($phpStackTrace, -1);
        } catch (ClarityContextRuntimeException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);
    }



    /**
     * Test the method that prepares a stack trace.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_prepare_stack_trace_method(): void
    {
        $phpStackTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
        $preparedStackTrace = Support::preparePHPStackTrace($phpStackTrace);



        // check the function of the earliest frame
        $lastKey = array_key_last($preparedStackTrace);
        self::assertSame('[top]', $preparedStackTrace[$lastKey]['function']);



        // test that at least the files and lines are correct
        // test that the functions are shifted by one frame

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

        // build a representation of the frames based on the prepared stack trace
        $preparedStackTraceFrames = [];
        foreach ($preparedStackTrace as $frame) {
            $preparedStackTraceFrames[] = [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
            ];
        }

        self::assertSame($phpStackTraceFrames, $preparedStackTraceFrames);



        // check that the 'object' field in each frame has been turned into its spl_object_id (i.e. an integer)
        foreach ($preparedStackTrace as $frameData) {
            self::assertTrue(is_int($frameData['object'] ?? -1));
        }



        // check that the extra frame added by call_user_func_array() (that's missing its file and line) is removed
        $pretendStackTrace = [
            [
                'file' => '', // <<<
                'line' => 0, // <<<
            ],
            [
                'file' => 'file2',
                'line' => 124,
            ],
            [
                'file' => 'file1',
                'line' => 123,
            ],
        ];
        $preparedStackTrace = Support::preparePHPStackTrace($pretendStackTrace);
        self::assertCount(2, $preparedStackTrace);
        self::assertSame('file2', $preparedStackTrace[0]['file']);
        self::assertSame(124, $preparedStackTrace[0]['line']);
    }





    /**
     * Test that Laravel exception handler frames can be removed.
     *
     * @test
     * @dataProvider exceptionHandlerFramesDataProvider
     *
     * @param integer $startFrameCount The number of frames to start with.
     * @param integer $addFrames       The number of frames to add.
     * @return void
     */
    #[Test]
    #[DataProvider('exceptionHandlerFramesDataProvider')]
    public static function test_that_laravel_exception_handler_frames_are_pruned(int $startFrameCount, int $addFrames)
    {
        $phpStackTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
        $stackTrace = Support::preparePHPStackTrace($phpStackTrace, __FILE__, __LINE__);
        $stackTrace = array_slice($stackTrace, 0, $startFrameCount);
        $stackTrace = self::addLaravelExceptionHandlerFrames($stackTrace, $addFrames);

        $origCount = count($stackTrace);
        $stackTrace = Support::pruneLaravelExceptionHandlerFrames($stackTrace);

        self::assertCount($origCount - $addFrames, $stackTrace);
    }

    /**
     * DataProvider for test_that_laravel_exception_handler_frames_are_pruned().
     *
     * @return array<array<string,integer>>
     */
    public static function exceptionHandlerFramesDataProvider(): array
    {
        return [
            ['startFrameCount' => 5, 'addFrames' => 0],
            ['startFrameCount' => 5, 'addFrames' => 1],
            ['startFrameCount' => 5, 'addFrames' => 2],

            ['startFrameCount' => 1, 'addFrames' => 0],
            ['startFrameCount' => 1, 'addFrames' => 1],
            ['startFrameCount' => 1, 'addFrames' => 2],

            ['startFrameCount' => 0, 'addFrames' => 0],
            ['startFrameCount' => 0, 'addFrames' => 1],
            ['startFrameCount' => 0, 'addFrames' => 2],
        ];
    }

    /**
     * Add some Laravel exception handler frames to a stack trace.
     *
     * @param array<integer, mixed[]> $stackTrace The stack trace to add the frames to.
     * @param integer                 $addFrames  The number of frames to add.
     * @return array<integer, mixed[]>
     */
    private static function addLaravelExceptionHandlerFrames(array $stackTrace, int $addFrames): array
    {
        $newFrames = [
            [
                'file' => '/var/www/html/vendor/'
                    . 'laravel/framework/src/Illuminate/Foundation/Bootstrap/HandleExceptions.php',
                'line' => 254,
                'function' => 'Illuminate\Foundation\Bootstrap\{closure}',
                'class' => 'Illuminate\Foundation\Bootstrap\HandleExceptions',
                'type' => '->',
            ],
            [
                'file' => '/var/www/html/routes/web.php',
                'line' => 51,
                'function' => 'handleError',
                'class' => 'Illuminate\Foundation\Bootstrap\HandleExceptions',
                'type' => '->',
            ],
        ];

        $newFrames = array_slice($newFrames, 0, $addFrames);

        return array_merge($newFrames, $stackTrace);
    }
}
