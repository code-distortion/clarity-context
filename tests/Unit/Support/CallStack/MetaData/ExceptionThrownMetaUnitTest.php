<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\CallStack\MetaData;

use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionThrownMeta;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;

/**
 * Test the ExceptionThrownMeta class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ExceptionThrownMetaUnitTest extends PHPUnitTestCase
{
    /**
     * Test the retrieval of data from the ExceptionThrownMeta class.
     *
     * @test
     *
     * @return void
     */
    public static function test_exception_thrown_meta(): void
    {
        $rand = mt_rand();
        $file = "/var/www/html/path/to/file.$rand.php";
        $projectFile = "/path/to/file.$rand.php";
        $line = $rand;
        $function = "something$rand";
        $class = "someClass$rand";

        foreach (['->', '::'] as $type) {

            $frameData = [
                'file' => $file,
                'line' => $line,
                'function' => $function,
                'class' => $class,
                'type' => $type,
            ];

            $meta = new ExceptionThrownMeta($frameData, $projectFile);

            self::assertSame($file, $meta->getFile());
            self::assertSame($projectFile, $meta->getProjectFile());
            self::assertSame($line, $meta->getLine());
            self::assertSame($function, $meta->getFunction());
            self::assertSame($class, $meta->getClass());
            self::assertSame($type, $meta->getType());
        }
    }
}
