<?php

namespace CodeDistortion\ClarityContext\Support\Framework\Config;

use CodeDistortion\ClarityContext\Support\InternalSettings;

/**
 * Interacting with the Laravel's configuration.
 */
class LaravelFrameworkConfig extends AbstractFrameworkConfig
{
    /**
     * Retrieve the project-root directory.
     *
     * @return string
     */
    public static function getProjectRootDir(): string
    {
        $path = self::isUsingTestbench()
            ? realpath(base_path('../../../../'))
            : realpath(base_path());

        return is_string($path)
            ? $path . DIRECTORY_SEPARATOR
            : '';
    }



    /**
     * Retrieve the enabled setting.
     *
     * @return boolean|null
     */
    public static function getEnabled(): ?bool
    {
        return self::pickConfigBoolean(InternalSettings::LARAVEL_CONTEXT__CONFIG_NAME . '.enabled');
    }



    /**
     * Retrieve the channels to use when the exception is "known".
     *
     * @return string[]
     */
    public static function getChannelsWhenKnown(): array
    {
        // see if it's a string first, so it can be split by commas below
        $channels = self::pickConfigString(InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known')
            ?? self::pickConfigStringArray(InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known');

        return is_array($channels)
            ? $channels
            : explode(',', $channels);
    }

    /**
     * Retrieve the default channels to use.
     *
     * @return string[]
     */
    public static function getChannelsWhenNotKnown(): array
    {
        $key = InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known';

        // see if it's a string first, so it can be split by commas below
        $channels = self::pickConfigString($key)
            ?? self::pickConfigStringArray($key);

        return is_array($channels)
            ? $channels
            : explode(',', $channels);
    }

    /**
     * Retrieve the framework's default channels.
     *
     * @return string[]
     */
    public static function getFrameworkDefaultChannels(): array
    {
        return array_filter(
            [self::pickConfigString('logging.default') ?: 'stack']
        );
    }



    /**
     * Retrieve the level to use when the exception is "known".
     *
     * @return string|null
     */
    public static function getLevelWhenKnown(): ?string
    {
        return self::pickConfigString(InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.level.when_known');
    }

    /**
     * Retrieve the default level to use.
     *
     * @return string|null
     */
    public static function getLevelWhenNotKnown(): ?string
    {
        return self::pickConfigString(InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.level.when_not_known');
    }



    /**
     * Retrieve the report setting to use.
     *
     * @return boolean|null
     */
    public static function getReport(): ?bool
    {
        return self::pickConfigBoolean(InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.report');
    }





    /**
     * Update the framework's config with new values (used while running tests).
     *
     * Note: For frameworks other than Laravel, the keys will need to be converted from Laravel's keys.
     *
     * @internal
     *
     * @param mixed[] $values The values to store.
     * @return void
     */
    public static function updateConfig(array $values): void
    {
        config($values);
    }





    /**
     * Pick a boolean from Laravel's config.
     *
     * @internal
     *
     * @param string $key The key to look for.
     * @return boolean|null
     */
    public static function pickConfigBoolean(string $key): ?bool
    {
        $value = config($key);

        return is_bool($value)
            ? $value
            : null;
    }

    /**
     * Pick a string from Laravel's config.
     *
     * @internal
     *
     * @param string $key The key to look for.
     * @return string|null
     */
    public static function pickConfigString(string $key): ?string
    {
        $value = config($key);

        return (is_string($value)) && ($value !== '')
            ? $value
            : null;
    }

    /**
     * Pick a string or array of strings from Laravel's config. Returns them as an array.
     *
     * @internal
     *
     * @param string $key The key to look for.
     * @return string[]
     */
    public static function pickConfigStringArray(string $key): array
    {
        $values = config($key);

        if (is_string($values)) {
            return $values !== ''
                ? [$values]
                : [];
        }

        return is_array($values)
            ? $values
            : [];
    }

    /**
     * Work out if Orchestra Testbench is being used.
     *
     * @return boolean
     */
    private static function isUsingTestbench(): bool
    {
        $testBenchDir = '/vendor/orchestra/testbench-core/laravel';
        // @infection-ignore-all - UnwrapStrReplace - always gives the same result on linux
        $testBenchDir = str_replace('/', DIRECTORY_SEPARATOR, $testBenchDir);
        return str_ends_with(base_path(), $testBenchDir);
    }
}
