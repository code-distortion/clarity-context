<?php

namespace CodeDistortion\ClarityContext\Support\CallStack\MetaData;

/**
 * Represents context details that were added to the callstack.
 */
class ContextMeta extends Meta
{
    /**
     * Constructor.
     *
     * @param mixed[]        $frameData   The debug_backtrace frame data.
     * @param string         $projectFile The file's location in relation to the project-root.
     * @param string|mixed[] $context     The context details.
     */
    public function __construct(
        protected array $frameData,
        protected string $projectFile,
        protected string|array $context,
    ) {
    }



    /**
     * Get the context details.
     *
     * @return string|mixed[]
     */
    public function getContext(): string|array
    {
        return $this->context;
    }
}
