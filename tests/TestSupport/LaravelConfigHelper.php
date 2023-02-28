<?php

namespace CodeDistortion\ClarityContext\Tests\TestSupport;

use CodeDistortion\ClarityContext\Support\Framework\Framework;
use CodeDistortion\ClarityContext\Support\InternalSettings;

/**
 * Helper methods to interact with Laravel's config.
 */
class LaravelConfigHelper
{
    /**
     * Enable Clarity via the Laravel config.
     *
     * @return void
     */
    public static function enableClarity(): void
    {
        Framework::config()->updateConfig([InternalSettings::LARAVEL_CONTEXT__CONFIG_NAME . '.enabled' => true]);
    }

    /**
     * Disable Clarity via the Laravel config.
     *
     * @return void
     */
    public static function disableClarity(): void
    {
        Framework::config()->updateConfig([InternalSettings::LARAVEL_CONTEXT__CONFIG_NAME . '.enabled' => false]);
    }



    /**
     * Set the "report" setting.
     *
     * @param boolean|null $report The report setting to use.
     * @return void
     */
    public static function updateReportSetting(?bool $report): void
    {
        Framework::config()->updateConfig([InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.report' => $report]);
    }



    /**
     * Set the "channels when known" setting.
     *
     * @param string[] $channels The channels to set.
     * @return void
     */
    public static function updateChannelsWhenKnown(array $channels): void
    {
        Framework::config()->updateConfig(
            [InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_known' => $channels]
        );
    }

    /**
     * Set the "channels when not known" setting.
     *
     * @param string[] $channels The channels to set.
     * @return void
     */
    public static function updateChannelsWhenNotKnown(array $channels): void
    {
        Framework::config()->updateConfig(
            [InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.channels.when_not_known' => $channels]
        );
    }



    /**
     * Set the "level when known" setting.
     *
     * @param string $level The level to set.
     * @return void
     */
    public static function updateLevelWhenKnown(string $level): void
    {
        Framework::config()->updateConfig(
            [InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.level.when_known' => $level]
        );
    }

    /**
     * Set the "level when not known" setting.
     *
     * @param string $level The level to set.
     * @return void
     */
    public static function updateLevelWhenNotKnown(string $level): void
    {
        Framework::config()->updateConfig(
            [InternalSettings::LARAVEL_CONTROL__CONFIG_NAME . '.level.when_not_known' => $level]
        );
    }
}
