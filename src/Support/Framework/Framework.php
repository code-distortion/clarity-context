<?php

namespace CodeDistortion\ClarityContext\Support\Framework;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextInitialisationException;
use CodeDistortion\ClarityContext\Support\Environment;
use CodeDistortion\ClarityContext\Support\Framework\Config\FrameworkConfigInterface;
use CodeDistortion\ClarityContext\Support\Framework\Config\LaravelFrameworkConfig;
use CodeDistortion\ClarityContext\Support\Framework\DepInjection\FrameworkDepInjectionInterface;
use CodeDistortion\ClarityContext\Support\Framework\DepInjection\LaravelDepInjection;

/**
 * Provide an easy pathway to access the correct config and dependency-injection objects, based on the current
 * framework.
 */
class Framework
{
    /**
     * A cache of the object used to interact with the framework's configuration.
     *
     * Note: this is safe to store as a static property (which will bleed between test, jobs, octane requests, etc),
     * because the object itself doesn't contain any state. In fact, every method inside is static.
     *
     * @var FrameworkConfigInterface|null
     */
    private static ?FrameworkConfigInterface $frameworkConfig = null;

    /**
     * A cache of the dependency injection object.
     *
     * Note: this is safe to store as a static property (which will bleed between test, jobs, octane requests, etc),
     * because the object itself doesn't contain any state. In fact, every method inside is static.
     *
     * @var FrameworkDepInjectionInterface|null
     */
    private static ?FrameworkDepInjectionInterface $frameworkDepInjection = null;



    /**
     * Resolve which config instance to use.
     *
     * @return FrameworkConfigInterface
     * @throws ClarityContextInitialisationException When the current framework can't be determined.
     */
    public static function config(): FrameworkConfigInterface
    {
        return self::$frameworkConfig ??= self::buildNewFrameworkConfig();
    }

    /**
     * Build a new config instance.
     *
     * @return FrameworkConfigInterface
     * @throws ClarityContextInitialisationException When the current framework can't be determined.
     */
    private static function buildNewFrameworkConfig(): FrameworkConfigInterface
    {
        if (Environment::isLaravel()) {
            return new LaravelFrameworkConfig();
        }
        throw ClarityContextInitialisationException::unknownFramework();
    }



    /**
     * Resolve which dependency injection instance to use.
     *
     * @return FrameworkDepInjectionInterface
     * @throws ClarityContextInitialisationException When the current framework can't be determined.
     */
    public static function depInj(): FrameworkDepInjectionInterface
    {
        return self::$frameworkDepInjection ??= self::buildNewDepInjection();
    }

    /**
     * Build a new dependency injection instance.
     *
     * @return FrameworkDepInjectionInterface
     * @throws ClarityContextInitialisationException When the current framework can't be determined.
     */
    private static function buildNewDepInjection(): FrameworkDepInjectionInterface
    {
        if (Environment::isLaravel()) {
            return new LaravelDepInjection();
        }
        throw ClarityContextInitialisationException::unknownFramework();
    }
}
