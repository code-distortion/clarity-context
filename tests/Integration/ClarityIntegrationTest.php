<?php

namespace CodeDistortion\ClarityContext\Tests\Integration;

use CodeDistortion\ClarityContext\Clarity;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use Exception;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the Clarity class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ClarityIntegrationTest extends LaravelTestCase
{
    /**
     * Test that meta-data gets stored when calling Clarity methods using call_user_func_array(..).
     *
     * @test
     *
     * @return void
     * @throws Exception Doesn't throw this, but phpcs expects this to be here.
     */
    #[Test]
    public static function test_that_meta_data_is_remembered_when_calling_call_user_func_array()
    {
        call_user_func_array([new Clarity(), 'context'], ['something']);

        $context = Clarity::buildContextHere();
        $contextMetas = $context->getCallStack()->getMeta(ContextMeta::class);
        $contextMeta = $contextMetas[0];

        $method = 'test_that_meta_data_is_remembered_when_calling_call_user_func_array';
        $class = 'CodeDistortion\ClarityContext\Tests\Integration\ClarityIntegrationTest';

        self::assertCount(1, $contextMetas);
        self::assertSame(__FILE__, $contextMeta->getFile());
        self::assertSame(__LINE__ - 11, $contextMeta->getLine());
        self::assertSame($method, $contextMeta->getFunction());
        self::assertSame($class, $contextMeta->getClass());
    }
}
