<?php

namespace CodeDistortion\ClarityContext\Support\Framework\DepInjection;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;

/**
 * Use Laravel to manage dependency injection.
 */
class LaravelDepInjection implements FrameworkDepInjectionInterface
{
//    private static array $cache = [];



    /**
     * Get a value (or class instance) using the dependency container.
     *
     * @param string $key     The key to retrieve.
     * @param mixed  $default The default value to fall back to (will be executed when callable).
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
//        if (isset(self::$cache[$key])) {
//            return self::$cache[$key];
//        }
//        return is_callable($default)
//            ? $default()
//            : $default;



        /** @var Application $app */
        $app = app();

        try {

            return $app->make($key);

        } catch (BindingResolutionException) {
        }

        return is_callable($default)
            ? $app->call($default)
            : $default;
    }

    /**
     * Get a value (or class instance) using the dependency container. Will store the default when not present.
     *
     * @param string $key     The key to retrieve.
     * @param mixed  $default The default value to fall back to (will be executed when callable).
     * @return mixed
     */
    public static function getOrSet(string $key, mixed $default): mixed
    {
//        if (!isset(self::$cache[$key])) {
//            self::$cache[$key] = is_callable($default)
//                ? $default()
//                : $default;
//        }
//        return self::$cache[$key];



        /** @var Application $app */
        $app = app();

        try {

            $return = $app->make($key);

            if (!is_null($return)) {
                return $return;
            }

        } catch (BindingResolutionException) {
        }

        $return = is_callable($default)
            ? $app->call($default)
            : $default;

        self::set($key, $return);

        return $return;
    }

    /**
     * Store a value or class instance in the dependency container.
     *
     * @param string $key   The key to set.
     * @param mixed  $value The value to set.
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
//        self::$cache[$key] = $value;
//        return;



        /** @var Application $app */
        $app = app();

        // @infection-ignore-all - Ternary - both scoped(..) and singleton(..) give the same result in the context of
        // tests
        method_exists($app, 'scoped')
            ? $app->scoped($key, fn() => $value)
            : $app->singleton($key, fn() => $value);
    }

    /**
     * Create a concrete instance of a class using the dependency container.
     *
     * @param string  $abstract   The class to instantiate.
     * @param mixed[] $parameters The constructor parameters to pass.
     * @return mixed
     */
    public static function make(string $abstract, array $parameters = []): mixed
    {
        /** @var Application $app */
        $app = app();
        return $app->make($abstract, $parameters);
    }

    /**
     * Run a callable, resolving parameters first using the dependency container.
     *
     * @param callable $callable   The callable to run.
     * @param mixed[]  $parameters The parameters to pass.
     * @return mixed
     */
    public static function call(callable $callable, array $parameters = []): mixed
    {
//        return call_user_func_array($callable, $parameters);



        /** @var Application $app */
        $app = app();
        return $app->call($callable, $parameters);
    }
}
