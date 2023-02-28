<?php

namespace CodeDistortion\ClarityContext\Tests\TestSupport;

/**
 * A simple value object.
 */
class ValueObject
{
    /**
     * Constructor.
     *
     * @param string|null $value The value to store.
     */
    public function __construct(private ?string $value = null)
    {
    }

    /**
     * Get the value.
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
