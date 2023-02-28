<?php

namespace CodeDistortion\ClarityContext\Tests;

use CodeDistortion\ClarityContext\ServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

/**
 * The Laravel test case.
 */
abstract class LaravelTestCase extends TestCase
{
    /**
     * Get package providers.
     *
     * @param Application $app The Laravel app.
     * @return array<int, class-string>
     */
    // phpcs:ignore
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class
        ];
    }
}
