<?php

namespace CodeDistortion\ClarityContext\Support\Framework\Config;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextInitialisationException;

/**
 * Interface for interacting with the current framework's configuration.
 */
interface FrameworkConfigInterface
{
    /**
     * Retrieve the project-root directory.
     *
     * @return string
     */
    public static function getProjectRootDir(): string;



    /**
     * Retrieve the enabled setting.
     *
     * @return boolean|null
     */
    public static function getEnabled(): ?bool;



    /**
     * Retrieve the channels to use when the exception is "known".
     *
     * @return string[]
     */
    public static function getChannelsWhenKnown(): array;

    /**
     * Retrieve the default channels to use.
     *
     * @return string[]
     */
    public static function getChannelsWhenNotKnown(): array;

    /**
     * Retrieve the framework's default channels.
     *
     * @return string[]
     */
    public static function getFrameworkDefaultChannels(): array;

    /**
     * Pick the best channels to use.
     *
     * @param boolean $isKnown Whether the exception has "known" issues or not.
     * @return string[]
     */
    public static function pickBestChannels(bool $isKnown): array;





    /**
     * Retrieve the level to use when the exception is "known".
     *
     * @return string|null
     */
    public static function getLevelWhenKnown(): ?string;

    /**
     * Retrieve the default level to use.
     *
     * @return string|null
     */
    public static function getLevelWhenNotKnown(): ?string;

    /**
     * Pick the best log reporting level to use.
     *
     * @param boolean $isKnown Whether the exception has "known" issues or not.
     * @return string|null
     * @throws ClarityContextInitialisationException When an invalid level is picked.
     */
    public static function pickBestLevel(bool $isKnown): ?string;



    /**
     * Retrieve the report setting to use.
     *
     * @return boolean|null
     */
    public static function getReport(): ?bool;





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
    public static function updateConfig(array $values): void;





    /**
     * Pick a boolean from Laravel's config.
     *
     * @internal
     *
     * @param string $key The key to look for.
     * @return boolean|null
     */
    public static function pickConfigBoolean(string $key): ?bool;

    /**
     * Pick a string from Laravel's config.
     *
     * @internal
     *
     * @param string $key The key to look for.
     * @return string|null
     */
    public static function pickConfigString(string $key): ?string;

    /**
     * Pick a string or array of strings from Laravel's config. Returns them as an array.
     *
     * @internal
     *
     * @param string $key The key to look for.
     * @return string[]
     */
    public static function pickConfigStringArray(string $key): array;
}
