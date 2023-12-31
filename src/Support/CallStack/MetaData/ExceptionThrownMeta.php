<?php

namespace CodeDistortion\ClarityContext\Support\CallStack\MetaData;

/**
 * Represents where the exception was thrown.
 */
class ExceptionThrownMeta extends Meta
{
    /**
     * Constructor.
     *
     * @param mixed[] $frameData   The debug_backtrace frame data.
     * @param string  $projectFile The file's location in relation to the project-root.
     */
    public function __construct(
        protected array $frameData,
        protected string $projectFile,
    ) {
    }
}
