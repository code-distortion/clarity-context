<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Support\MetaCallStack;
use CodeDistortion\ClarityContext\Support\Support;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\PHPStackTraceHelper;
use CodeDistortion\ClarityContext\Tests\TestSupport\SomeOtherClass;
use Exception;

/**
 * Test the MetaCallStack class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class MetaCallStackUnitTest extends PHPUnitTestCase
{
    /**
     * Test that MetaCallStack can record meta-data x steps back.
     *
     * Also test ->getStackMetaData() method.
     *
     * @test
     *
     * @return void
     */
    public static function test_storage_of_meta_data_x_steps_back(): void
    {
        // start empty
        $metaCallStack = new MetaCallStack();
        self::assertSame([], $metaCallStack->getStackMetaData());



        // go back x steps, and check what was stored each time
        $currentFrameIndex = PHPStackTraceHelper::getCurrentFrameIndex();
        for ($count = -1; $count <= $currentFrameIndex + 1; $count++) {

            $expectException = ($count == -1 || $count == $currentFrameIndex + 1);

            $caughtException = false;
            try {

                $metaCallStack->pushMultipleMetaDataValues("type$count", null, ["value$count"], $count);

                $metaData = $metaCallStack->getStackMetaData();
                self::assertSame([$currentFrameIndex - $count], array_keys($metaData));

                self::assertSame("type$count", $metaData[$currentFrameIndex - $count][0]['type']);
                self::assertSame("value$count", $metaData[$currentFrameIndex - $count][0]['value']);

            } catch (ClarityContextRuntimeException) {
                $caughtException = true;
            }
            self::assertSame($expectException, $caughtException);
        }
    }



    /**
     * Test that MetaCallStack can record different sorts of meta-data.
     *
     * @test
     *
     * @return void
     */
    public static function test_storage_of_different_sorts_of_meta_data(): void
    {
        $metaCallStack = new MetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues('typeA', null, ['valueA'], 0);
        $metaCallStack->pushMultipleMetaDataValues('typeB', null, ['valueB'], 0);

        $currentFrameIndex = PHPStackTraceHelper::getCurrentFrameIndex();

        $metaData = $metaCallStack->getStackMetaData();
        self::assertSame([$currentFrameIndex], array_keys($metaData));
        self::assertSame([0, 1], array_keys($metaData[$currentFrameIndex]));
        self::assertSame('typeA', $metaData[$currentFrameIndex][0]['type']);
        self::assertSame('valueA', $metaData[$currentFrameIndex][0]['value']);
        self::assertSame('typeB', $metaData[$currentFrameIndex][1]['type']);
        self::assertSame('valueB', $metaData[$currentFrameIndex][1]['value']);
    }



    /**
     * Test that MetaCallStack can update a meta-data's value.
     *
     * @test
     *
     * @return void
     */
    public static function test_replacement_of_a_meta_datas_value(): void
    {
        $metaCallStack = new MetaCallStack();



        // set the "typeA" meta-data up initially
        $metaCallStack->pushMultipleMetaDataValues('typeA', 123, [['fieldA' => 'a', 'fieldB' => 'b']], 0);

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertCount(1, $metaData[$lastIndex] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame(['fieldA' => 'a', 'fieldB' => 'b'], $metaData[$lastIndex][0]['value']);



        // try to update the identifier 456 (which DOESN'T exist) meta-data's value
        $metaCallStack->replaceMetaDataValue('typeXXX', 456, ['fieldA' => 'A', 'fieldB' => 'b']);
        $metaData = $metaCallStack->getStackMetaData();

        // check that there's NO change
        self::assertCount(1, $metaData[$lastIndex] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame(['fieldA' => 'a', 'fieldB' => 'b'], $metaData[$lastIndex][0]['value']);



        // update the typeA meta-data's value by searching for identifier 'XXX', which DOESN'T exist
        $metaCallStack->replaceMetaDataValue('typeA', 'XXX', ['fieldA' => 'A', 'fieldB' => 'b']);
        $metaData = $metaCallStack->getStackMetaData();

        // check that there's NO change
        self::assertCount(1, $metaData[$lastIndex] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame(['fieldA' => 'a', 'fieldB' => 'b'], $metaData[$lastIndex][0]['value']);



        // update the typeXXX meta-data's value by searching for identifier 123, which DOESN'T exist
        // (everything matches, but the type is wrong)
        $metaCallStack->replaceMetaDataValue('typeXXX', 123, ['fieldA' => 'A', 'fieldB' => 'b']);
        $metaData = $metaCallStack->getStackMetaData();

        // check that there's NO change
        self::assertCount(1, $metaData[$lastIndex] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame(['fieldA' => 'a', 'fieldB' => 'b'], $metaData[$lastIndex][0]['value']);



        // update the typeA meta-data's value by searching for identifier 123, which DOES exist
        $metaCallStack->replaceMetaDataValue('typeA', 123, ['fieldA' => 'A', 'fieldB' => 'b']);
        $metaData = $metaCallStack->getStackMetaData();

        // check that there IS a change
        self::assertCount(1, $metaData[$lastIndex] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame(['fieldA' => 'A', 'fieldB' => 'b'], $metaData[$lastIndex][0]['value']);
    }



    /**
     * Test that the meta-data is pruned when adding meta-data.
     *
     * Test adding meta-data of different types.
     *
     * Test replacing meta-data with $removeOthers = true.
     *
     * @test
     *
     * @return void
     */
    public static function test_pruning_when_adding_meta_data(): void
    {
        $metaCallStack = new MetaCallStack();

        // add the first add two meta-data, with $removeOthers = false
        $metaCallStack->pushMultipleMetaDataValues('typeA', null, ['valueA1'], 0);
        $metaCallStack->pushMultipleMetaDataValues('typeA', null, ['valueA2'], 0);

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertCount(2, $metaData[$lastIndex] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueA1', $metaData[$lastIndex][0]['value']);
        self::assertSame('typeA', $metaData[$lastIndex][1]['type']);
        self::assertSame('valueA2', $metaData[$lastIndex][1]['value']);



        // add new meta-data to the next frame, with $removeOthers = true (won't remove anything)
        $a = fn() => $metaCallStack->pushMultipleMetaDataValues('typeA', null, ['valueA3'], 0, ['typeA']);
        $a();

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        // same as before on this frame
        self::assertCount(2, $metaData[$lastIndex - 1] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex - 1][0]['type']);
        self::assertSame('valueA1', $metaData[$lastIndex - 1][0]['value']);
        self::assertSame('typeA', $metaData[$lastIndex - 1][1]['type']);
        self::assertSame('valueA2', $metaData[$lastIndex - 1][1]['value']);

        // but with new meta-data on this frame
        self::assertCount(1, $metaData[$lastIndex] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueA3', $metaData[$lastIndex][0]['value']);



        // add new meta-data, with $removeOthers = true (back to this frame this time, will remove others)
        $metaCallStack->pushMultipleMetaDataValues('typeA', null, ['valueA3'], 0, ['typeA']);

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        // only this new meta-data on this frame
        self::assertCount(1, $metaData[$lastIndex] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueA3', $metaData[$lastIndex][0]['value']);



        // add a different type, with $removeOthers = true (which won't remove anything)
        $metaCallStack->pushMultipleMetaDataValues('typeB', null, ['valueB1'], 0, ['typeB']);

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        // both meta-data was added
        self::assertCount(2, $metaData[$lastIndex] ?? []);
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueA3', $metaData[$lastIndex][0]['value']);
        self::assertSame('typeB', $metaData[$lastIndex][1]['type']);
        self::assertSame('valueB1', $metaData[$lastIndex][1]['value']);
    }



    /**
     * Test that the meta-data is pruned based on a stack trace.
     *
     * @test
     *
     * @return void
     */
    public static function test_pruning_based_on_a_stack_trace(): void
    {
        // add some meta-data in this frame
        $metaCallStack = new MetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues('typeA', null, ['valueA'], 0);



        // add some meta-data inside a closure (i.e. in the next frame from this one)
        $a = function (MetaCallStack $metaCallStack, string $type, string $value) {
            $metaCallStack->pushMultipleMetaDataValues($type, null, [$value], 0);
        };
        $a($metaCallStack, 'typeB', 'valueB');

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex1 = max(array_keys($metaData));



        // now prune off the last frame
        $phpStackTrace = PHPStackTraceHelper::buildPHPStackTraceHere();
        $preparedStackTrace = Support::preparePHPStackTrace($phpStackTrace);
        $callstack = array_reverse($preparedStackTrace);
        $metaCallStack->pruneBasedOnRegularCallStack($callstack);

        // check that valueB was pruned off
        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex2 = max(array_keys($metaData));

        self::assertSame($lastIndex1 - 1, $lastIndex2);
        self::assertCount(1, $metaData[$lastIndex2]);
        self::assertSame('typeA', $metaData[$lastIndex2][0]['type']);
        self::assertSame('valueA', $metaData[$lastIndex2][0]['value']);
    }



    /**
     * Test that the meta-data is pruned based on an exception.
     *
     * @test
     *
     * @return void
     */
    public static function test_pruning_based_on_an_exception(): void
    {
        // build a new MetaCallStack instance - also add some initial meta-data in the previous frame
        $newMetaCallStack = function (): MetaCallStack {
            $metaCallStack = new MetaCallStack();
            $metaCallStack->pushMultipleMetaDataValues('typeA', null, ['valueA'], 1);
            return $metaCallStack;
        };
        // add some meta-data inside a closure (i.e. in the next frame from this one)
        $pushMetaData = function (MetaCallStack $metaCallStack, string $type, string $value) {
            $metaCallStack->pushMultipleMetaDataValues($type, null, [$value], 0);
        };
        // generate an exception in a later frame
        $generateException = function (): Exception {
            return new Exception();
        };



        // exception thrown in an EARLIER FRAME than valueB meta-data was added
        // call from the SAME LINE
        $metaCallStack = $newMetaCallStack();
        $pushMetaData($metaCallStack, 'typeB', 'valueB'); $e = new Exception(); // phpcs:ignore
        self::pruneWithExceptionAndCheck($metaCallStack, $e, false, false);

        // call from DIFFERENT LINES
        $metaCallStack = $newMetaCallStack();
        $pushMetaData($metaCallStack, 'typeB', 'valueB');
        $e = new Exception();
        self::pruneWithExceptionAndCheck($metaCallStack, $e, false, false);



        // exception thrown in THE SAME FRAME as valueB meta-data was added
        // call from the SAME LINE
        $metaCallStack = $newMetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues('typeB', null, ['valueB'], 0); $e = new Exception(); // phpcs:ignore
        self::pruneWithExceptionAndCheck($metaCallStack, $e, true, true);

        // call from DIFFERENT LINES
        $metaCallStack = $newMetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues('typeB', null, ['valueB'], 0);
        $e = new Exception();
        self::pruneWithExceptionAndCheck($metaCallStack, $e, true, true);



        // exception thrown in A DIFFERENT FRAME as valueB meta-data was added, but at the SAME DEPTH
        // call from the SAME LINE
        // NOTE: this one calls two different closures on the same line, and suffers the same problem as test
        // test_meta_data_pruning_when_calling_different_closures_on_the_same_line(), where Clarity can't tell the
        // difference between the closure's frames, and thinks they're the same
        $metaCallStack = $newMetaCallStack();
        $pushMetaData($metaCallStack, 'typeB', 'valueB'); $e = $generateException(); // phpcs:ignore
        self::pruneWithExceptionAndCheck($metaCallStack, $e, true, false);

        // call from DIFFERENT LINES
        $metaCallStack = $newMetaCallStack();
        $pushMetaData($metaCallStack, 'typeB', 'valueB');
        $e = $generateException();
        self::pruneWithExceptionAndCheck($metaCallStack, $e, false, false);

        // call from the SAME LINE - but generate the exception from another class
        $metaCallStack = $newMetaCallStack();
        $pushMetaData($metaCallStack, 'typeB', 'valueB'); $e = SomeOtherClass::generateException(); // phpcs:ignore
        self::pruneWithExceptionAndCheck($metaCallStack, $e, false, false);



        // exception thrown in a LATER FRAME than valueB meta-data was added
        // call from the SAME LINE
        $metaCallStack = $newMetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues('typeB', null, ['valueB'], 0); $e = $generateException(); // phpcs:ignore
        self::pruneWithExceptionAndCheck($metaCallStack, $e, true, true);

        // call from DIFFERENT LINES
        $metaCallStack = $newMetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues('typeB', null, ['valueB'], 0);
        $e = $generateException();
        self::pruneWithExceptionAndCheck($metaCallStack, $e, true, true);
    }



    /**
     * Prune a MetaCallStack based on an an Exception's stack trace, and check the results.
     *
     * @param MetaCallStack $metaCallStack     The MetaCallStack to check.
     * @param Exception     $e                 The exception to use.
     * @param boolean       $shouldValueBExist Should the "valueB" meta-data exist after pruning?.
     * @param boolean       $sameFrameAsValueA If so, should it exist in the same frame as the "valueA" meta-data, or
     *                                         the next?.
     * @return void
     */
    private static function pruneWithExceptionAndCheck(
        MetaCallStack $metaCallStack,
        Exception $e,
        bool $shouldValueBExist,
        bool $sameFrameAsValueA
    ): void {

        // prune off the based on the exception
        $preparedStackTrace = Support::preparePHPStackTrace($e->getTrace(), $e->getFile(), $e->getLine());
        $callstack = array_reverse($preparedStackTrace);
        $metaCallStack->pruneBasedOnExceptionCallStack($callstack);

        $metaData = $metaCallStack->getStackMetaData();

        $valueAFrameIndex = min(array_keys($metaData));
        $lastFrameIndex = max(array_keys($metaData));

        if ($shouldValueBExist) {
            if ($sameFrameAsValueA) {
                self::assertSame($valueAFrameIndex, $lastFrameIndex);

                self::assertSame(2, count($metaData[$valueAFrameIndex]));
                self::assertSame('typeA', $metaData[$valueAFrameIndex][0]['type']);
                self::assertSame('valueA', $metaData[$valueAFrameIndex][0]['value']);
                self::assertSame('typeB', $metaData[$valueAFrameIndex][1]['type']);
                self::assertSame('valueB', $metaData[$valueAFrameIndex][1]['value']);
            } else {
                self::assertSame($valueAFrameIndex + 1, $lastFrameIndex);

                self::assertSame(1, count($metaData[$valueAFrameIndex]));
                self::assertSame('typeA', $metaData[$valueAFrameIndex][0]['type']);
                self::assertSame('valueA', $metaData[$valueAFrameIndex][0]['value']);

                self::assertSame(1, count($metaData[$lastFrameIndex]));
                self::assertSame('typeB', $metaData[$lastFrameIndex][0]['type']);
                self::assertSame('valueB', $metaData[$lastFrameIndex][0]['value']);
            }
        } else {
            self::assertSame($valueAFrameIndex, $lastFrameIndex);

            self::assertSame(1, count($metaData[$valueAFrameIndex]));
            self::assertSame('typeA', $metaData[$valueAFrameIndex][0]['type']);
            self::assertSame('valueA', $metaData[$valueAFrameIndex][0]['value']);
        }
    }





    /**
     * Test that meta-data gets pruned properly: when meta-data is added via THE SAME CLOSURE, called twice from the
     * same line.
     *
     * NOTE: Because of the data that PHP's debug_backtrace() provides, calls *occurring on the same line* to closure/s
     * make it look like the inside of closure/s are in the same frame. The meta-data won't be pruned.
     *
     * This test just confirms this "known" behaviour, even though it is incorrect.
     *
     * @test
     *
     * @return void
     */
    public static function test_meta_data_pruning_when_calling_the_same_closure_on_the_same_line(): void
    {
        $metaCallStack = new MetaCallStack();



        // add some meta-data inside a closure (i.e. in the next frame from this one)
        $a = function (MetaCallStack $metaCallStack, string $type, string $value) {
            $metaCallStack->pushMultipleMetaDataValues($type, null, [$value], 0);
        };

        // unfortunately, PHP doesn't let us see the difference between the two closure calls using debug_stacktrace()
        // so Clarity considers the two calls to ->pushMultipleMetaData(..) (in the closure above) to be in the same
        // frame. Both of the meta-data values added above will be recorded, and present below
        $a($metaCallStack, 'typeA', 'valueA'); $a($metaCallStack, 'typeB', 'valueB'); // phpcs:ignore

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertSame(2, count($metaData[$lastIndex]));
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueA', $metaData[$lastIndex][0]['value']);
        self::assertSame('typeB', $metaData[$lastIndex][1]['type']);
        self::assertSame('valueB', $metaData[$lastIndex][1]['value']);



        // when the closure calls are on DIFFERENT LINES, Clarity CAN tell the difference between them, and will
        // prune the meta-data properly
        $a($metaCallStack, 'typeA', 'valueA');
        $a($metaCallStack, 'typeB', 'valueB');

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertSame(1, count($metaData[$lastIndex]));
        self::assertSame('typeB', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueB', $metaData[$lastIndex][0]['value']);
    }

    /**
     * Test that meta-data gets pruned properly: when meta-data is added via TWO DIFFERENT CLOSURES, called from the
     * same line.
     *
     * NOTE: Because of the data that PHP's debug_backtrace() provides, calls *occurring on the same line* to closure/s
     * make it look like the inside of closure/s are in the same frame. The meta-data won't be pruned.
     *
     * This test just confirms this "known" behaviour, even though it is incorrect.
     *
     * @test
     *
     * @return void
     */
    public static function test_meta_data_pruning_when_calling_different_closures_on_the_same_line(): void
    {
        $metaCallStack = new MetaCallStack();



        // add some meta-data inside a closure (i.e. in the next frame from this one)
        $a = function (MetaCallStack $metaCallStack, string $type, string $value) {
            $metaCallStack->pushMultipleMetaDataValues($type, null, [$value], 0);
        };

        // add some meta-data inside a closure (i.e. in the next frame from this one)
        $b = function (MetaCallStack $metaCallStack, string $type, string $value) {
            $metaCallStack->pushMultipleMetaDataValues($type, null, [$value], 0);
        };

        // unfortunately, PHP doesn't let us see the difference between the two closures using debug_stacktrace()
        // so Clarity considers the two calls to ->pushMultipleMetaData(..) (in the closures above) to be in the same
        // frame. Both of the meta-data values added above will be recorded, and present below
        $a($metaCallStack, 'typeA', 'valueA'); $b($metaCallStack, 'typeB', 'valueB'); // phpcs:ignore

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertSame(2, count($metaData[$lastIndex]));
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueA', $metaData[$lastIndex][0]['value']);
        self::assertSame('typeB', $metaData[$lastIndex][1]['type']);
        self::assertSame('valueB', $metaData[$lastIndex][1]['value']);



        // when the closure calls are on DIFFERENT LINES, Clarity CAN tell the difference between them, and will
        // prune the meta-data properly
        $a($metaCallStack, 'typeA', 'valueA');
        $b($metaCallStack, 'typeB', 'valueB');

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertSame(1, count($metaData[$lastIndex]));
        self::assertSame('typeB', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueB', $metaData[$lastIndex][0]['value']);
    }

    /**
     * Test that meta-data gets pruned properly: when meta-data is added via THE SAME STATIC METHOD, called twice from
     * the same line.
     *
     * NOTE: Because of the data that PHP's debug_backtrace() provides, calls *occurring on the same line* to a static
     * method make it look like the inside of the static method is in the same frame for both calls. The meta-data won't
     * be pruned.
     *
     * This test just confirms this "known" behaviour, even though it is incorrect.
     *
     * @test
     *
     * @return void
     */
    public static function test_meta_data_pruning_when_calling_the_same_static_method_on_the_same_line(): void
    {
        $metaCallStack = new MetaCallStack();



        // unfortunately, PHP doesn't let us see the difference between the two static method calls using
        // debug_stacktrace(), so Clarity considers the two calls to ->pushMultipleMetaData(..) (inside the static
        // method) to be in the same frame. Both of the meta-data values added above will be recorded, and present below
        self::addMetaDataA($metaCallStack, 'typeA', 'valueA'); self::addMetaDataA($metaCallStack, 'typeB', 'valueB'); // phpcs:ignore

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertSame(2, count($metaData[$lastIndex]));
        self::assertSame('typeA', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueA', $metaData[$lastIndex][0]['value']);
        self::assertSame('typeB', $metaData[$lastIndex][1]['type']);
        self::assertSame('valueB', $metaData[$lastIndex][1]['value']);



        // when the static method calls are on DIFFERENT LINES, Clarity CAN tell the difference between them, and
        // will prune the meta-data properly
        self::addMetaDataA($metaCallStack, 'typeA', 'valueA');
        self::addMetaDataA($metaCallStack, 'typeB', 'valueB'); // phpcs:ignore

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertSame(1, count($metaData[$lastIndex]));
        self::assertSame('typeB', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueB', $metaData[$lastIndex][0]['value']);
    }

    /**
     * Test that meta-data gets pruned properly: when meta-data is added via TWO DIFFERENT STATIC METHODS, called from
     * the same line.
     *
     * @test
     *
     * @return void
     */
    public static function test_meta_data_pruning_when_calling_different_static_methods_on_the_same_line(): void
    {
        $metaCallStack = new MetaCallStack();



        // when the static method calls are on the *same line*, Clarity CAN tell the difference between them, and will
        // prune the meta-data properly
        self::addMetaDataA($metaCallStack, 'typeA', 'valueA'); self::addMetaDataB($metaCallStack, 'typeB', 'valueB'); // phpcs:ignore

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertSame(1, count($metaData[$lastIndex]));
        self::assertSame('typeB', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueB', $metaData[$lastIndex][0]['value']);



        // when the static method calls are on DIFFERENT LINES, Clarity CAN tell the difference between them, and
        // will prune the meta-data properly
        self::addMetaDataA($metaCallStack, 'typeA', 'valueA');
        self::addMetaDataB($metaCallStack, 'typeB', 'valueB'); // phpcs:ignore

        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertSame(1, count($metaData[$lastIndex]));
        self::assertSame('typeB', $metaData[$lastIndex][0]['type']);
        self::assertSame('valueB', $metaData[$lastIndex][0]['value']);
    }

    /**
     * Add some meta-data to a MetaCallStack for the caller.
     *
     * @param MetaCallStack $metaCallStack The MetaCallStack to add meta-data to.
     * @param string        $type          The "type" of meta-data to add.
     * @param string        $value         The meta-data value to add.
     * @return void
     */
    private static function addMetaDataA(MetaCallStack $metaCallStack, string $type, string $value): void
    {
        $metaCallStack->pushMultipleMetaDataValues($type, null, [$value], 0);
    }

    /**
     * Add some meta-data to a MetaCallStack for the caller (this one is here just because it's a different method to
     * the on above).
     *
     * @param MetaCallStack $metaCallStack The MetaCallStack to add meta-data to.
     * @param string        $type          The "type" of meta-data to add.
     * @param string        $value         The meta-data value to add.
     * @return void
     */
    private static function addMetaDataB(MetaCallStack $metaCallStack, string $type, string $value): void
    {
        $metaCallStack->pushMultipleMetaDataValues($type, null, [$value], 0);
    }





    /**
     * Test the addition of multiple meta-data values from the same call.
     *
     * @test
     *
     * @return void
     */
    public static function test_the_addition_of_multiple_meta_data_from_the_same_call(): void
    {
        // add some meta-data to start with
        $metaCallStack = new MetaCallStack();
        $metaCallStack->pushMultipleMetaDataValues('typeZ', null, ['valueZ'], 0);



        // closure to add multiple meta-data
        $a = function (string $type, array $multipleMetaData) use (&$metaCallStack) {
            $metaCallStack->pushMultipleMetaDataValues($type, null, $multipleMetaData, 1);
        };

        // closure to count how many meta-data objects are currently stored, and record the result
        $countsPerIteration = [];
        $addCount = function () use (&$metaCallStack, &$countsPerIteration) {
            $metaData = $metaCallStack->getStackMetaData();
            if (!count($metaData)) {
                $countsPerIteration[] = 0;
                return;
            }
            $lastIndex = max(array_keys($metaData));
            $countsPerIteration[] = count($metaData[$lastIndex]);
        };



        // loop twice, so meta-data is added on the same line for a second time
        $possibleValues = [
            'a' => ['valueA1a', 'valueA2a'],
            'b' => ['valueA1b'],
            'c' => ['valueA1c', 'valueA2c', 'valueA3c'],
        ];
        $addCount();
        foreach (['a', 'b', 'c'] as $i) {
            $a('typeA', $possibleValues[$i]); $addCount(); // phpcs:ignore
            $a('typeB', ["valueB1$i"]); $addCount(); // phpcs:ignore
        }



        // check the meta-data counts for each iteration
        self::assertSame([1, 3, 4, 3, 3, 5, 5], $countsPerIteration);

        // check the meta-data that's left
        $metaData = $metaCallStack->getStackMetaData();
        $lastIndex = max(array_keys($metaData));

        self::assertSame(5, count($metaData[$lastIndex]));
        self::assertSame('typeZ', $metaData[$lastIndex][0]['type']); // the initial meta-data
        self::assertSame('valueZ', $metaData[$lastIndex][0]['value']);
        self::assertSame('typeA', $metaData[$lastIndex][1]['type']);
        self::assertSame('valueA1c', $metaData[$lastIndex][1]['value']);
        self::assertSame('typeA', $metaData[$lastIndex][2]['type']);
        self::assertSame('valueA2c', $metaData[$lastIndex][2]['value']);
        self::assertSame('typeA', $metaData[$lastIndex][3]['type']);
        self::assertSame('valueA3c', $metaData[$lastIndex][3]['value']);
        self::assertSame('typeB', $metaData[$lastIndex][4]['type']);
        self::assertSame('valueB1c', $metaData[$lastIndex][4]['value']);
    }
}
