<?php

namespace CodeDistortion\ClarityContext\API;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Support\Framework\Framework;
use CodeDistortion\ClarityContext\Support\Support;

/**
 * Methods to push and replace meta-data in the "global" MetaCallStack instance.
 *
 * They're automatically get pruned as the call stack moves on.
 */
class MetaCallStackAPI
{
    /**
     * Add a meta-data value to the "global" MetaCallStack.
     *
     * @param string              $type               The "type" of meta-data to add.
     * @param integer|string|null $identifier         An identifier to use when replacing meta-data later (can be
     *                                                shared).
     * @param string|mixed[]      $metaData           The meta-data value to add.
     * @param integer             $framesBack         The number of frames to go back, to get the intended caller frame.
     * @param string[]            $removeTypesFromTop The meta-data types to remove from the top of the stack.
     * @return void
     * @throws ClarityContextRuntimeException When an invalid number of steps to go back is given.
     */
    public static function pushMetaData(
        string $type,
        int|string|null $identifier,
        string|array $metaData,
        int $framesBack = 0,
        array $removeTypesFromTop = [],
    ): void {

        if (!Framework::config()->getEnabled()) {
            return;
        }

        if ($framesBack < 0) {
            throw ClarityContextRuntimeException::invalidFramesBack($framesBack);
        }

        Support::getGlobalMetaCallStack()
            ->pushMultipleMetaDataValues($type, $identifier, [$metaData], $framesBack + 1, $removeTypesFromTop);
    }

    /**
     * Add multiple meta-data values to the "global" MetaCallStack (is quicker than adding each separately).
     *
     * @param string                $type               The "type" of meta-data to add.
     * @param integer|string|null   $identifier         Required when updating meta-data later (can be shared).
     * @param array<string|mixed[]> $multipleMetaData   An array of meta-data values to add.
     * @param integer               $framesBack         The number of frames to go back, to get the intended caller
     *                                                  frame.
     * @param string[]              $removeTypesFromTop The meta-data types to remove from the top of the stack.
     * @return void
     * @throws ClarityContextRuntimeException When an invalid number of steps to go back is given.
     */
    public static function pushMultipleMetaData(
        string $type,
        int|string|null $identifier,
        array $multipleMetaData,
        int $framesBack = 0,
        array $removeTypesFromTop = [],
    ): void {

        if (!Framework::config()->getEnabled()) {
            return;
        }

        if ($framesBack < 0) {
            throw ClarityContextRuntimeException::invalidFramesBack($framesBack);
        }

        Support::getGlobalMetaCallStack()
            ->pushMultipleMetaDataValues($type, $identifier, $multipleMetaData, $framesBack + 1, $removeTypesFromTop);
    }

    /**
     * Update some meta-data in the "global" MetaCallStack with a new value.
     *
     * @param string         $type                The "type" of meta-data to update.
     * @param integer|string $identifier          The identifier to find.
     * @param string|mixed[] $replacementMetaData The replacement meta-data value.
     * @return void
     */
    public static function replaceMetaData(
        string $type,
        int|string $identifier,
        string|array $replacementMetaData
    ): void {

        if (!Framework::config()->getEnabled()) {
            return;
        }

        Support::getGlobalMetaCallStack()
            ->replaceMetaDataValue($type, $identifier, $replacementMetaData);
    }
}
