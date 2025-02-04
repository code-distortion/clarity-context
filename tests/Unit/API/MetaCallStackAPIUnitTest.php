<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\API;

use CodeDistortion\ClarityContext\API\MetaCallStackAPI;
use CodeDistortion\ClarityContext\Clarity;
use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Support\InternalSettings;
use CodeDistortion\ClarityContext\Support\Support;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\LaravelConfigHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the MetaCallStackAPI class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class MetaCallStackAPIUnitTest extends LaravelTestCase
{
    /**
     * Test the MetaCallStackAPI pushMetaData method when Clarity has been disabled.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_push_meta_data_method_when_clarity_is_disabled(): void
    {
        LaravelConfigHelper::disableClarity();

        MetaCallStackAPI::pushMetaData('type-a', null, 'hello 1');

        $metaCallStack = Support::getGlobalMetaCallStack();
        $metaData = $metaCallStack->getStackMetaData();
        self::assertCount(0, $metaData);
    }

    /**
     * Test the MetaCallStackAPI pushMetaData method.
     *
     * @test
     *
     * @return void
     */
     #[Test]
    public static function test_push_meta_data_method(): void
    {
        // add some meta-data
        MetaCallStackAPI::pushMetaData('type-a', null, 'hello 1 a', 1);
        MetaCallStackAPI::pushMetaData('type-b', null, 'hello 2 b', 1);
        MetaCallStackAPI::pushMetaData('type-a', 123, 'hello 3 a'); // $framesBack implicit
        MetaCallStackAPI::pushMetaData('type-a', 'some-id', 'hello 4 a', 0); // $framesBack explicit
        MetaCallStackAPI::pushMetaData('type-b', 123, 'hello 5 b');
        MetaCallStackAPI::pushMetaData('type-b', 'some-id', 'hello 6 b');

        // check which meta-data was stored
        $metaCallStack = Support::getGlobalMetaCallStack();
        $metaData = $metaCallStack->getStackMetaData();

        $frameCount = count(debug_backtrace());
        self::assertSame(range($frameCount - 1, $frameCount), array_keys($metaData));

        self::assertSame('type-a', $metaData[$frameCount - 1][0]['type'] ?? null);
        self::assertSame(null, $metaData[$frameCount - 1][0]['identifier'] ?? null);
        self::assertSame('hello 1 a', $metaData[$frameCount - 1][0]['value'] ?? null);

        self::assertSame('type-b', $metaData[$frameCount - 1][1]['type'] ?? null);
        self::assertSame(null, $metaData[$frameCount - 1][1]['identifier'] ?? null);
        self::assertSame('hello 2 b', $metaData[$frameCount - 1][1]['value'] ?? null);

        self::assertSame('type-a', $metaData[$frameCount][0]['type'] ?? null);
        self::assertSame(123, $metaData[$frameCount][0]['identifier'] ?? null);
        self::assertSame('hello 3 a', $metaData[$frameCount][0]['value'] ?? null);

        self::assertSame('type-a', $metaData[$frameCount][1]['type'] ?? null);
        self::assertSame('some-id', $metaData[$frameCount][1]['identifier'] ?? null);
        self::assertSame('hello 4 a', $metaData[$frameCount][1]['value'] ?? null);

        self::assertSame('type-b', $metaData[$frameCount][2]['type'] ?? null);
        self::assertSame(123, $metaData[$frameCount][2]['identifier'] ?? null);
        self::assertSame('hello 5 b', $metaData[$frameCount][2]['value'] ?? null);

        self::assertSame('type-b', $metaData[$frameCount][3]['type'] ?? null);
        self::assertSame('some-id', $metaData[$frameCount][3]['identifier'] ?? null);
        self::assertSame('hello 6 b', $metaData[$frameCount][3]['value'] ?? null);
    }

    /**
     * Test the MetaCallStackAPI pushMetaData method when invalid $framesBack has been specified.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_push_meta_data_method_with_invalid_frames_back(): void
    {
        // test going back too many frames
        $caughtException = false;
        try {
            // generate an exception
            MetaCallStackAPI::pushMetaData('type-a', null, 'hello 1', -1);
        } catch (ClarityContextRuntimeException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);
    }






    /**
     * Test the MetaCallStackAPI pushMultipleMetaData method when Clarity has been disabled.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_push_multiple_meta_data_method_when_clarity_is_disabled(): void
    {
        LaravelConfigHelper::disableClarity();

        MetaCallStackAPI::pushMultipleMetaData('type-a', null, ['hello 1']);

        $metaCallStack = Support::getGlobalMetaCallStack();
        $metaData = $metaCallStack->getStackMetaData();
        self::assertCount(0, $metaData);
    }

    /**
     * Test the MetaCallStackAPI pushMultipleMetaData method.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_push_multiple_meta_data_method(): void
    {
        // add some meta-data
        MetaCallStackAPI::pushMultipleMetaData('type-a', null, ['hello 1 a', 'hello 2 a'], 1);
        MetaCallStackAPI::pushMultipleMetaData('type-a', 123, ['hello 3 a']); // $framesBack implicit
        MetaCallStackAPI::pushMultipleMetaData('type-a', 'some-id', ['hello 4 a'], 0); // $framesBack explicit
        MetaCallStackAPI::pushMultipleMetaData('type-b', 123, ['hello 5 b']);
        MetaCallStackAPI::pushMultipleMetaData('type-b', 'some-id', ['hello 6 b']);

        // check which meta-data was stored
        $metaCallStack = Support::getGlobalMetaCallStack();
        $metaData = $metaCallStack->getStackMetaData();

        $frameCount = count(debug_backtrace());
        self::assertSame(range($frameCount - 1, $frameCount), array_keys($metaData));

        self::assertSame('type-a', $metaData[$frameCount - 1][0]['type'] ?? null);
        self::assertSame(null, $metaData[$frameCount - 1][0]['identifier'] ?? null);
        self::assertSame('hello 1 a', $metaData[$frameCount - 1][0]['value'] ?? null);

        self::assertSame('type-a', $metaData[$frameCount - 1][1]['type'] ?? null);
        self::assertSame(null, $metaData[$frameCount - 1][1]['identifier'] ?? null);
        self::assertSame('hello 2 a', $metaData[$frameCount - 1][1]['value'] ?? null);

        self::assertSame('type-a', $metaData[$frameCount][0]['type'] ?? null);
        self::assertSame(123, $metaData[$frameCount][0]['identifier'] ?? null);
        self::assertSame('hello 3 a', $metaData[$frameCount][0]['value'] ?? null);

        self::assertSame('type-a', $metaData[$frameCount][1]['type'] ?? null);
        self::assertSame('some-id', $metaData[$frameCount][1]['identifier'] ?? null);
        self::assertSame('hello 4 a', $metaData[$frameCount][1]['value'] ?? null);

        self::assertSame('type-b', $metaData[$frameCount][2]['type'] ?? null);
        self::assertSame(123, $metaData[$frameCount][2]['identifier'] ?? null);
        self::assertSame('hello 5 b', $metaData[$frameCount][2]['value'] ?? null);

        self::assertSame('type-b', $metaData[$frameCount][3]['type'] ?? null);
        self::assertSame('some-id', $metaData[$frameCount][3]['identifier'] ?? null);
        self::assertSame('hello 6 b', $metaData[$frameCount][3]['value'] ?? null);
    }

    /**
     * Test the MetaCallStackAPI pushMultipleMetaData method when invalid $framesBack has been specified.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_push_multiple_meta_data_method_with_invalid_frames_back(): void
    {
        // test going back too many frames
        $caughtException = false;
        try {
            // generate an exception
            MetaCallStackAPI::pushMultipleMetaData('type-a', null, ['hello 1'], -1);
        } catch (ClarityContextRuntimeException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);
    }





    /**
     * Test the MetaCallStackAPI pushMetaData method when Clarity has been disabled.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_replace_meta_data_method_when_clarity_is_disabled(): void
    {
        // add some meta-data + try to replace, even though Clarity has been disabled
        MetaCallStackAPI::pushMetaData('type-a', 123, 'hello 1 a');
        LaravelConfigHelper::disableClarity();
        MetaCallStackAPI::replaceMetaData('type-a', 123, 'new 1');

        $metaCallStack = Support::getGlobalMetaCallStack();
        $metaData = $metaCallStack->getStackMetaData();

        $frameCount = count(debug_backtrace());

        self::assertSame('hello 1 a', $metaData[$frameCount][0]['value'] ?? null);
    }

    /**
     * Test the MetaCallStackAPI pushMetaData method.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_replace_meta_data_method(): void
    {
        $summariseMetaData = function () {
            $metaCallStack = Support::getGlobalMetaCallStack();
            $metaData = $metaCallStack->getStackMetaData();

            $return = [];
            foreach (array_keys($metaData) as $frameCount) {
                foreach ($metaData[$frameCount] as $index => $oneMetaData) {
                    /** @var string $value Just pretend. */
                    $value = $oneMetaData['value'];
                    $return[] = "$frameCount $index $value";
                }
            }

            return $return;
        };



        // add some meta-data
        MetaCallStackAPI::pushMetaData('type-a', 123, 'hello 1 a', 2);
        MetaCallStackAPI::pushMetaData('type-b', null, 'hello 2 b', 2);
        MetaCallStackAPI::pushMetaData('type-a', 123, 'hello 3 a', 1);
        MetaCallStackAPI::pushMetaData('type-b', 123, 'hello 4 b', 1);
        MetaCallStackAPI::pushMetaData('type-a', '456', 'hello 5 a', 0);
        MetaCallStackAPI::pushMetaData('type-a', 789, 'hello 6 a', 0);

        $frameCount = count(debug_backtrace());



        // initial state
        $a = [
            ($frameCount - 2) . " 0 hello 1 a",
            ($frameCount - 2) . " 1 hello 2 b",
            ($frameCount - 1) . " 0 hello 3 a",
            ($frameCount - 1) . " 1 hello 4 b",
            ($frameCount) . " 0 hello 5 a",
            ($frameCount) . " 1 hello 6 a",
        ];
        self::assertSame($a, $summariseMetaData());



        // test type-a with identifier 123
        MetaCallStackAPI::replaceMetaData('type-a', 123, 'new 1');
        $a = [
            ($frameCount - 2) . " 0 new 1",
            ($frameCount - 2) . " 1 hello 2 b",
            ($frameCount - 1) . " 0 new 1",
            ($frameCount - 1) . " 1 hello 4 b",
            ($frameCount) . " 0 hello 5 a",
            ($frameCount) . " 1 hello 6 a",
        ];
        self::assertSame($a, $summariseMetaData());



        // test type-b with identifier 123
        MetaCallStackAPI::replaceMetaData('type-b', 123, 'new 2');
        $a = [
            ($frameCount - 2) . " 0 new 1",
            ($frameCount - 2) . " 1 hello 2 b",
            ($frameCount - 1) . " 0 new 1",
            ($frameCount - 1) . " 1 new 2",
            ($frameCount) . " 0 hello 5 a",
            ($frameCount) . " 1 hello 6 a",
        ];
        self::assertSame($a, $summariseMetaData());



        // test when the identifier doesn't exist
        MetaCallStackAPI::replaceMetaData('type-b', 'doesnt-exist', 'new 3');
        $a = [
            ($frameCount - 2) . " 0 new 1",
            ($frameCount - 2) . " 1 hello 2 b",
            ($frameCount - 1) . " 0 new 1",
            ($frameCount - 1) . " 1 new 2",
            ($frameCount) . " 0 hello 5 a",
            ($frameCount) . " 1 hello 6 a",
        ];
        self::assertSame($a, $summariseMetaData());



        // test type-a with identifier 456 (INTEGER identifier) (no change) (i.e. test identifier TYPE)
        MetaCallStackAPI::replaceMetaData('type-a', 456, 'new 4');
        $a = [
            ($frameCount - 2) . " 0 new 1",
            ($frameCount - 2) . " 1 hello 2 b",
            ($frameCount - 1) . " 0 new 1",
            ($frameCount - 1) . " 1 new 2",
            ($frameCount) . " 0 hello 5 a",
            ($frameCount) . " 1 hello 6 a",
        ];
        self::assertSame($a, $summariseMetaData());



        // test type-a with identifier 456 (STRING identifier) (i.e. test identifier TYPE)
        MetaCallStackAPI::replaceMetaData('type-a', '456', 'new 5');
        $a = [
            ($frameCount - 2) . " 0 new 1",
            ($frameCount - 2) . " 1 hello 2 b",
            ($frameCount - 1) . " 0 new 1",
            ($frameCount - 1) . " 1 new 2",
            ($frameCount) . " 0 new 5",
            ($frameCount) . " 1 hello 6 a",
        ];
        self::assertSame($a, $summariseMetaData());
    }





    /**
     * Test that the InternalSettings::META_DATA_TYPE__CONTROL_CALL meta-data is removed from the top of the stack.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_that_control_run_meta_is_removed_from_top(): void
    {
        MetaCallStackAPI::pushMultipleMetaData(InternalSettings::META_DATA_TYPE__CONTROL_CALL, null, ['call-one']);
        MetaCallStackAPI::pushMultipleMetaData(InternalSettings::META_DATA_TYPE__CONTEXT, null, ['context-one']);

        $meta = Support::getGlobalMetaCallStack()->getStackMetaData();
        $key = array_key_last($meta);
        self::assertCount(2, $meta[$key]);
        self::assertSame(['call-one', 'context-one'], [$meta[$key][0]['value'], $meta[$key][1]['value']]);



        MetaCallStackAPI::pushMultipleMetaData(
            InternalSettings::META_DATA_TYPE__CONTEXT,
            null,
            ['context-two'],
            0,
            [InternalSettings::META_DATA_TYPE__CONTEXT], // <<< remove these from the top
        );

        $meta = Support::getGlobalMetaCallStack()->getStackMetaData();
        self::assertCount(2, $meta[$key]);
        self::assertSame(['call-one', 'context-two'], [$meta[$key][0]['value'], $meta[$key][1]['value']]);



        // check that Clarity::context(…) removes the InternalSettings::META_DATA_TYPE__CONTROL_CALL meta-data
        Clarity::context('context-three');

        $meta = Support::getGlobalMetaCallStack()->getStackMetaData();
        self::assertCount(2, $meta[$key]);
        self::assertSame(['context-two', 'context-three'], [$meta[$key][0]['value'], $meta[$key][1]['value']]);
    }
}
