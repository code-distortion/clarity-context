<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\CallStack;

use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionThrownMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaGroup;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use stdClass;

/**
 * Test the MetaGroup class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class MetaGroupUnitTest extends PHPUnitTestCase
{
    /**
     * Test the basic MetaGroup interactions
     *
     * @test
     * @dataProvider metaGroupCrudDataProvider
     *
     * @param boolean $isInApplicationFrame       Whether $isInApplicationFrame will be set to true or false.
     * @param boolean $isInLastApplicationFrame   Whether $isInLastApplicationFrame will be set to true or false.
     * @param boolean $isInLastFrame              Whether $isInLastFrame will be set to true or false.
     * @param boolean $exceptionThrownInThisFrame Whether $exceptionThrownInThisFrame will be set to true or false.
     * @param boolean $exceptionCaughtInThisFrame Whether $exceptionCaughtInThisFrame will be set to true or false.
     * @return void
     */
    public static function test_meta_group_crud(
        bool $isInApplicationFrame,
        bool $isInLastApplicationFrame,
        bool $isInLastFrame,
        bool $exceptionThrownInThisFrame,
        bool $exceptionCaughtInThisFrame,
    ): void {

        $file = 'some-file';
        $projectFile = 'some-project-file';
        $line = 123;
        $function = 'func';
        $class = 'SomeClass';
        $type = '::';

        $frameData = [
            'file' => $file,
            'line' => $line,
            'function' => $function,
            'class' => $class,
            'object' => new stdClass(),
            'type' => $type,
            'args' => []
        ];

        $meta = new ExceptionThrownMeta($frameData, $projectFile);
        $metaObjects = [$meta];

        $frame = new Frame(
            $frameData,
            $projectFile,
            $metaObjects,
            $isInApplicationFrame,
            $isInLastApplicationFrame,
            $isInLastFrame,
            $exceptionThrownInThisFrame,
            $exceptionCaughtInThisFrame,
        );

        // test when instantiated via the constructor
        $metaGroup1 = new MetaGroup(
            $file,
            $projectFile,
            $line,
            $function,
            $class,
            $type,
            $metaObjects,
            $isInApplicationFrame,
            $isInLastApplicationFrame,
            $isInLastFrame,
            $exceptionThrownInThisFrame,
            $exceptionCaughtInThisFrame,
        );

        self::assertSame($file, $metaGroup1->getFile());
        self::assertSame($projectFile, $metaGroup1->getProjectFile());
        self::assertSame($line, $metaGroup1->getLine());
        self::assertSame($function, $metaGroup1->getFunction());
        self::assertSame($class, $metaGroup1->getClass());
        self::assertSame($type, $metaGroup1->getType());
        self::assertSame($metaObjects, $metaGroup1->getMeta());
        self::assertSame($isInApplicationFrame, $metaGroup1->isInApplicationFrame());
        self::assertSame($isInLastApplicationFrame, $metaGroup1->isInLastApplicationFrame());
        self::assertSame(!$isInApplicationFrame, $metaGroup1->isInVendorFrame());
        self::assertSame($isInLastFrame, $metaGroup1->isInLastFrame());
        self::assertSame($exceptionThrownInThisFrame, $metaGroup1->exceptionThrownInThisFrame());
        self::assertSame($exceptionCaughtInThisFrame, $metaGroup1->exceptionCaughtInThisFrame());

        // test when instantiated via the alternative method
        $metaGroup2 = MetaGroup::newFromFrameAndMeta($frame, $meta, $metaObjects);
        self::assertEquals($metaGroup1, $metaGroup2);
    }

    /**
     * DataProvider for test_meta_group_crud().
     *
     * @return array<array<string, boolean>>
     */
    public static function metaGroupCrudDataProvider(): array
    {
        $return = [];

        foreach ([true, false] as $isInApplicationFrame) {
            foreach ([true, false] as $isInLastApplicationFrame) {
                foreach ([true, false] as $isInLastFrame) {
                    foreach ([true, false] as $exceptionThrownInThisFrame) {
                        foreach ([true, false] as $exceptionCaughtInThisFrame) {

                            $return[] = [
                                'isInApplicationFrame' => $isInApplicationFrame,
                                'isInLastApplicationFrame' => $isInLastApplicationFrame,
                                'isInLastFrame' => $isInLastFrame,
                                'exceptionThrownInThisFrame' => $exceptionThrownInThisFrame,
                                'exceptionCaughtInThisFrame' => $exceptionCaughtInThisFrame,
                            ];
                        }
                    }
                }
            }
        }

        return $return;
    }
}
