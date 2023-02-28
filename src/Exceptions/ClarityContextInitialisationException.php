<?php

namespace CodeDistortion\ClarityContext\Exceptions;

use CodeDistortion\ClarityContext\Settings;

/**
 * Exception generated when initialising Clarity Context.
 */
class ClarityContextInitialisationException extends ClarityContextException
{
    /**
     * The current framework type cannot be resolved.
     *
     * @return self
     */
    public static function unknownFramework(): self
    {
        return new self('The current framework type could not be resolved');
    }

    /**
     * An invalid level was specified.
     *
     * @param string|null $level The invalid level.
     * @return self
     */
    public static function levelNotAllowed(?string $level): self
    {
        return new self("Level \"$level\" is not allowed. Please choose from: " . implode(', ', Settings::LOG_LEVELS));
    }

    /**
     * Invalid meta-data was added to the MetaCallStack, and can't be used.
     *
     * @param string $type The invalid meta type.
     * @return self
     */
    public static function invalidMetaType(string $type): self
    {
        return new self("Invalid meta type \"$type\"");
    }
}
