<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\CallStack\MetaData;

use CodeDistortion\ClarityContext\Support\CallStack\MetaData\CallMeta;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the CallMeta class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CallMetaUnitTest extends PHPUnitTestCase
{
    /**
     * Test the retrieval of data from the CallMeta class.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_call_meta(): void
    {
        $rand = mt_rand();
        $file = "/var/www/html/path/to/file.$rand.php";
        $projectFile = "/path/to/file.$rand.php";
        $line = $rand;
        $function = "something$rand";
        $class = "someClass$rand";
        $known = ["known-$rand"];

        foreach ([true, false] as $caughtHere) {
            foreach (['->', '::'] as $type) {

                $frameData = [
                    'file' => $file,
                    'line' => $line,
                    'function' => $function,
                    'class' => $class,
                    'type' => $type,
                ];

                $meta = new CallMeta($frameData, $projectFile, $caughtHere, $known);

                self::assertSame($file, $meta->getFile());
                self::assertSame($projectFile, $meta->getProjectFile());
                self::assertSame($line, $meta->getLine());
                self::assertSame($function, $meta->getFunction());
                self::assertSame($class, $meta->getClass());
                self::assertSame($type, $meta->getType());
                self::assertSame($caughtHere, $meta->wasCaughtHere());
                self::assertSame($known, $meta->getKnown());
            }
        }
    }
}
