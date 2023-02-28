<?php

namespace CodeDistortion\ClarityContext\Exceptions;

/**
 * Exception generated when using Clarity Context.
 */
class ClarityContextRuntimeException extends ClarityContextException
{
    /**
     * An invalid number of steps to go back was given.
     *
     * @param integer $framesBack The number of frames to go back.
     * @return self
     */
    public static function invalidFramesBack(int $framesBack): self
    {
        return new self("Invalid number of frames to go back: $framesBack");
    }

    /**
     * Can't go back that many frames.
     *
     * @param integer $framesBack The number of frames to go back.
     * @param integer $current    The current number of frames.
     * @return self
     */
    public static function tooManyFramesBack(int $framesBack, int $current): self
    {
        return new self(
            "Can't go back that many frames: $framesBack "
            . "(current number of frames: $current, there must be at least one left)"
        );
    }
}
