<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\Support\CallStack\MetaData;

use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the ContextMeta class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ContextMetaUnitTest extends PHPUnitTestCase
{
    /**
     * Test the retrieval of data from the ContextMeta class.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_context_meta(): void
    {
        $rand = mt_rand();
        $file = "/var/www/html/path/to/file.$rand.php";
        $projectFile = "/path/to/file.$rand.php";
        $line = $rand;
        $function = "something$rand";
        $class = "someClass$rand";

        $contextValueCombinations = [
            "context$rand",
            ['id' => $rand]
        ];

        foreach (['->', '::'] as $type) {
            foreach ($contextValueCombinations as $context) {

                $frameData = [
                    'file' => $file,
                    'line' => $line,
                    'function' => $function,
                    'class' => $class,
                    'type' => $type,
                ];

                $meta = new ContextMeta($frameData, $projectFile, $context);

                self::assertSame($file, $meta->getFile());
                self::assertSame($projectFile, $meta->getProjectFile());
                self::assertSame($line, $meta->getLine());
                self::assertSame($function, $meta->getFunction());
                self::assertSame($class, $meta->getClass());
                self::assertSame($type, $meta->getType());
                self::assertSame($context, $meta->getContext());
            }
        }
    }
}
