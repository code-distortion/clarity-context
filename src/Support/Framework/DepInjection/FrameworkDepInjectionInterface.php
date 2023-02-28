<?php

namespace CodeDistortion\ClarityContext\Support\Framework\DepInjection;

/**
 * Interface for using the current framework to manage dependency injection.
 */
interface FrameworkDepInjectionInterface
{
    /**
     * Get a value (or class instance) using the dependency container.
     *
     * @param string $key     The key to retrieve.
     * @param mixed  $default The default value to fall back to (will be executed when callable).
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed;

    /**
     * Get a value (or class instance) using the dependency container. Will store the default when not present.
     *
     * @param string $key     The key to retrieve.
     * @param mixed  $default The default value to fall back to (will be executed when callable).
     * @return mixed
     */
    public static function getOrSet(string $key, mixed $default): mixed;

    /**
     * Store a value or class instance in the dependency container.
     *
     * @param string $key   The key to set.
     * @param mixed  $value The value to set.
     * @return void
     */
    public static function set(string $key, mixed $value): void;

    /**
     * Create a concrete instance of a class using the dependency container.
     *
     * @param string  $abstract   The class to instantiate.
     * @param mixed[] $parameters The constructor parameters to pass.
     * @return mixed
     */
    public static function make(string $abstract, array $parameters = []): mixed;

    /**
     * Run a callable, resolving parameters first using the dependency container.
     *
     * @param callable $callable   The callable to run.
     * @param mixed[]  $parameters The parameters to pass.
     * @return mixed
     */
    public static function call(callable $callable, array $parameters = []): mixed;
}
