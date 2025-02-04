<?php

namespace CodeDistortion\ClarityContext\Tests\Unit;

use CodeDistortion\ClarityContext\API\ContextAPI;
use CodeDistortion\ClarityContext\API\DataAPI;
use CodeDistortion\ClarityContext\Clarity;
use CodeDistortion\ClarityContext\Context;
use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\Support;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\LaravelConfigHelper;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the Clarity class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ClarityUnitTest extends LaravelTestCase
{
    /**
     * Test that Clarity adds meta-data.
     *
     * @test
     * @dataProvider metaDataCombinationDataProvider
     *
     * @param string|string[]        $arg1     The first argument to pass.
     * @param string|string[]|null   $arg2     The second argument to pass (when not null).
     * @param string|string[]|null   $arg3     The third argument to pass (when not null).
     * @param array<string[]|string> $expected The expected meta-data values.
     * @return void
     */
    #[Test]
    #[DataProvider('metaDataCombinationDataProvider')]
    public static function test_the_addition_of_meta_data(
        string|array $arg1,
        string|array|null $arg2,
        string|array|null $arg3,
        array $expected,
    ): void {

        // add some meta-data
        if ((!is_null($arg3)) && (!is_null($arg2))) {
            Clarity::context($arg1, $arg2, $arg3);
        } elseif (!is_null($arg2)) {
            Clarity::context($arg1, $arg2);
        } else {
            Clarity::context($arg1);
        }

        // check what was added
        $metaCallStack = Support::getGlobalMetaCallStack();
        $metaData = $metaCallStack->getStackMetaData();
        $lastFrameIndex = max(array_keys(($metaData)));

        $foundContext = [];
        /** @var array<string,string|mixed[]> $metaData */
        foreach ($metaData[$lastFrameIndex] as $metaData) {
            $foundContext[] = $metaData['value'];
        }

        self::assertSame($expected, $foundContext);
    }

    /**
     * Test that Clarity doesn't add meta-data when disabled.
     *
     * @test
     * @dataProvider metaDataCombinationDataProvider
     *
     * @param string|string[]        $arg1     The first argument to pass.
     * @param string|string[]|null   $arg2     The second argument to pass (when not null).
     * @param string|string[]|null   $arg3     The third argument to pass (when not null).
     * @param array<string[]|string> $expected The expected meta-data values.
     * @return void
     */
    #[Test]
    #[DataProvider('metaDataCombinationDataProvider')]
    public static function test_the_addition_of_meta_data_when_disabled(
        string|array $arg1,
        string|array|null $arg2,
        string|array|null $arg3,
        array $expected,
    ): void {

        LaravelConfigHelper::disableClarity();

        // add some meta-data
        if ((!is_null($arg3)) && (!is_null($arg2))) {
            Clarity::context($arg1, $arg2, $arg3);
        } elseif (!is_null($arg2)) {
            Clarity::context($arg1, $arg2);
        } else {
            Clarity::context($arg1);
        }

        // check what was added - always empty - no MetaData objects
        self::assertEmpty(Support::getGlobalMetaCallStack()->getStackMetaData());
    }

    /**
     * DataProvider for test_the_addition_of_meta_data() and test_the_addition_of_meta_data_when_disabled().
     *
     * @return array<array<string,array<string[]|string>|string|null>>
     */
    public static function metaDataCombinationDataProvider(): array
    {
        return [
            [
                'arg1' => 'some context1',
                'arg2' => null,
                'arg3' => null,
                'expected' => [
                    'some context1',
                ],
            ],
            [
                'arg1' => ['some context1'],
                'arg2' => null,
                'arg3' => null,
                'expected' => [
                    ['some context1'],
                ],
            ],
            [
                'arg1' => 'some context1',
                'arg2' => 'some context2',
                'arg3' => null,
                'expected' => [
                    'some context1',
                    'some context2',
                ],
            ],
            [
                'arg1' => 'some context1',
                'arg2' => ['some context2'],
                'arg3' => null,
                'expected' => [
                    'some context1',
                    ['some context2'],
                ],
            ],
            [
                'arg1' => 'some context1',
                'arg2' => 'some context2',
                'arg3' => 'some context3',
                'expected' => [
                    'some context1',
                    'some context2',
                    'some context3',
                ],
            ],
            [
                'arg1' => 'some context1',
                'arg2' => 'some context2',
                'arg3' => ['some context3'],
                'expected' => [
                    'some context1',
                    'some context2',
                    ['some context3'],
                ],
            ],
        ];
    }



    /**
     * Test that Clarity can build a context object in arbitrary places (not based on an exception).
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_build_context_here_method(): void
    {
        $frameCount = count(debug_backtrace()) + 1;



        // test with no $framesBack specified
        $context = Clarity::buildContextHere();
        self::assertInstanceOf(Context::class, $context);
        self::assertCount($frameCount, $context->getCallStack());

        // have a quick look at the location of the top frame
        $path = '/tests/Unit/ClarityUnitTest.php';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        /** @var Frame[] $trace */
        $trace = $context->getStackTrace();
        self::assertSame(__FILE__, $trace[0]->getFile());
        self::assertSame($path, $trace[0]->getProjectFile());
        self::assertSame(__LINE__ - 11, $trace[0]->getLine());



        // test with some $framesBack specified
        $context = Clarity::buildContextHere(1);
        self::assertCount($frameCount - 1, $context->getCallStack());

        // have a quick look at the location of the 2nd top frame
        $path = '/vendor/phpunit/phpunit/src/Framework/TestCase.php';
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        /** @var Frame[] $trace */
        $trace = $context->getStackTrace();
        self::assertStringEndsWith($path, $trace[0]->getFile());
        self::assertStringEndsWith($path, $trace[0]->getProjectFile());



        // test going back too many frames
        $caughtException = false;
        try {
            // generate an exception
            Clarity::buildContextHere($frameCount); // invalid frames back
        } catch (ClarityContextRuntimeException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);



        // test an invalid number of steps to go back
        $caughtException = false;
        try {
            // generate an exception
            Clarity::buildContextHere(-1); // invalid frames back
        } catch (ClarityContextRuntimeException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);
    }



    /**
     * Test that Clarity can retrieve exception's Context objects.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_retrieval_of_exception_context_objects(): void
    {
        // no "latest" Context yet
        self::assertNull(ContextAPI::getLatestExceptionContext());

        // get - when the exception's Context wasn't set (the Context will be built)
        $e = new Exception();
        $context = Clarity::getExceptionContext($e);
        self::assertSame($e, $context->getException());

        // it was built and the "latest" Context is now available
        self::assertSame($context, ContextAPI::getLatestExceptionContext());

        // when the exception's Context was already set
        $context2 = Clarity::getExceptionContext($e);
        self::assertSame($context, $context2);
        self::assertSame($e, $context2->getException());

        // the latest context is set now
        self::assertSame($context, ContextAPI::getLatestExceptionContext());

        // generate a Context for a different exception
        $e2 = new Exception();
        self::assertNotSame($context, Clarity::getExceptionContext($e2));
    }



    /**
     * Test that Clarity can set trace identifiers.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_the_addition_of_trace_identifiers(): void
    {
        // no identifiers yet
        self::assertEmpty(DataAPI::getTraceIdentifiers());

        $identifiers = [];

        // 1 unnamed identifier
        $identifiers[''] = 'abc';
        Clarity::traceIdentifier('abc');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());

        // 1 unnamed identifier (overwrite it)
        $identifiers[''] = 'def';
        Clarity::traceIdentifier('def');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());

        // 1 unnamed and 1 named identifier
        $identifiers['ghi'] = 123;
        Clarity::traceIdentifier(123, 'ghi');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());

        // 1 unnamed and 1 named identifier (overwrite the new named one)
        $identifiers['ghi'] = 456;
        Clarity::traceIdentifier(456, 'ghi');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());

        // 1 unnamed and 2 named identifiers
        $identifiers['jkl'] = 'xyz';
        Clarity::traceIdentifier('xyz', 'jkl');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());
    }
}
