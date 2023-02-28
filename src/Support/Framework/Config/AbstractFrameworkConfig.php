<?php

namespace CodeDistortion\ClarityContext\Support\Framework\Config;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextInitialisationException;
use CodeDistortion\ClarityContext\Settings;

/**
 * Interacting with the current framework's configuration.
 */
abstract class AbstractFrameworkConfig implements FrameworkConfigInterface
{
    /**
     * Pick the best channels to use.
     *
     * @param boolean $isKnown Whether the exception has "known" issues or not.
     * @return string[]
     */
    public static function pickBestChannels(bool $isKnown): array
    {
        $channels = $isKnown
            ? static::getChannelsWhenKnown()
            : static::getChannelsWhenNotKnown();

        return $channels
            ?: static::getFrameworkDefaultChannels();
    }

    /**
     * Pick the best log reporting level to use.
     *
     * @param boolean $isKnown Whether the exception has "known" issues or not.
     * @return string|null
     * @throws ClarityContextInitialisationException When an invalid level is picked.
     */
    public static function pickBestLevel(bool $isKnown): ?string
    {
        $level = $isKnown
            ? static::getLevelWhenKnown()
            : static::getLevelWhenNotKnown();

        $level = (string) $level;
        if ($level === '') {
            return null;
        }

        if (!in_array($level, Settings::LOG_LEVELS)) {
            throw ClarityContextInitialisationException::levelNotAllowed($level);
        }

        return $level;
    }
}
