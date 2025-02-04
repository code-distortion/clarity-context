<?php

namespace CodeDistortion\ClarityContext\Tests\Unit\API;

use CodeDistortion\ClarityContext\API\DataAPI;
use CodeDistortion\ClarityContext\Tests\LaravelTestCase;
use CodeDistortion\ClarityContext\Tests\TestSupport\LaravelConfigHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the DataAPI class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class DataAPIUnitTest extends LaravelTestCase
{
    /**
     * Test that the DataAPI records and returns trace identifiers when Clarity is disabled.
     *
     * (They still work, this isn't disabled).
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_the_storage_and_retrieval_of_trace_identifiers_when_disabled(): void
    {
        LaravelConfigHelper::disableClarity();

        // 1 unnamed identifier
        $identifiers[''] = 'abc';
        DataAPI::traceIdentifier('abc');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());
    }
    /**
     * Test that the DataAPI records and returns trace identifiers.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_the_storage_and_retrieval_of_trace_identifiers(): void
    {
        // no identifiers yet
        self::assertEmpty(DataAPI::getTraceIdentifiers());

        $identifiers = [];

        // 1 unnamed identifier
        $identifiers[''] = 'abc';
        DataAPI::traceIdentifier('abc');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());

        // 1 unnamed identifier (overwrite it)
        $identifiers[''] = 'def';
        DataAPI::traceIdentifier('def');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());

        // 1 unnamed and 1 named identifier
        $identifiers['ghi'] = 123;
        DataAPI::traceIdentifier(123, 'ghi');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());

        // 1 unnamed and 1 named identifier (overwrite the new named one)
        $identifiers['ghi'] = 456;
        DataAPI::traceIdentifier(456, 'ghi');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());

        // 1 unnamed and 2 named identifiers
        $identifiers['jkl'] = 'xyz';
        DataAPI::traceIdentifier('xyz', 'jkl');
        self::assertSame($identifiers, DataAPI::getTraceIdentifiers());
    }
}
