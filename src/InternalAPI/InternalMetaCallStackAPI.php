<?php

namespace CodeDistortion\ClarityContext\InternalAPI;

use CodeDistortion\ClarityContext\Support\Framework\Framework;
use CodeDistortion\ClarityContext\Support\InternalSettings;
use CodeDistortion\ClarityContext\Support\MetaCallStack;

/**
 * Internal MetaCallStack API - for internal use.
 */
class InternalMetaCallStackAPI
{
    /**
     * Get the MetaCallStack from global storage (creates and stores a new one if it hasn't been set yet).
     *
     * @internal
     *
     * @return MetaCallStack
     */
    public static function getGlobalMetaCallStack(): MetaCallStack
    {
        /** @var MetaCallStack $return */
        $return = Framework::depInj()->getOrSet(
            InternalSettings::CONTAINER_KEY__META_CALL_STACK,
            fn() => new MetaCallStack()
        );

        return $return;
    }
}
