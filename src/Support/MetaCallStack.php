<?php

namespace CodeDistortion\ClarityContext\Support;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;

/**
 * Keep track of the PHP callstack, and associate context details to particular points in the stack.
 */
class MetaCallStack
{
    /** @var array<integer, mixed[]> The current PHP callstack. */
    private array $callStack = [];

    /** @var array<integer, array<integer, mixed[]>> The meta-data that's been linked to points in the callstack. */
    private array $stackMetaData = [];



    /**
     * Get the callstack's meta-data.
     *
     * @return array<integer, array<integer, mixed[]>>
     */
    public function getStackMetaData(): array
    {
        return $this->stackMetaData;
    }





    /**
     * Add some meta-data to the callstack.
     *
     * @param string                $type               The type of meta-data to save.
     * @param integer|string|null   $identifier         Required when updating meta-data later (can be shared).
     * @param array<string|mixed[]> $multipleMetaData   The meta-data values to save.
     * @param integer               $framesBack         The number of frames to go back, to get the intended caller
     *                                                  frame.
     * @param string[]              $removeTypesFromTop The meta-data types to remove from the top of the stack.
     * @return void
     * @throws ClarityContextRuntimeException When an invalid number of steps to go back is given.
     */
    public function pushMultipleMetaDataValues(
        string $type,
        int|string|null $identifier,
        array $multipleMetaData,
        int $framesBack,
        array $removeTypesFromTop = [],
    ): void {

        $phpStackTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
        $phpStackTrace = Support::stepBackStackTrace($phpStackTrace, $framesBack);
        $stackTrace = Support::preparePHPStackTrace($phpStackTrace);
        $callstack = array_reverse($stackTrace);

        $this->replaceCallStack($callstack);

        $lastFrame = array_pop($callstack) ?? [];

        $this->removeMetaDataFromTop($removeTypesFromTop);

        foreach (array_values($multipleMetaData) as $paramCount => $oneMetaData) {
            $this->recordMetaData($type, $identifier, $oneMetaData, $paramCount, $lastFrame);
        }
    }

//    /**
//     * Find particular existing meta-data throughout the callstack, and replace it.
//     *
//     * @param string  $type     The type of meta-data to update.
//     * @param string  $field    The field to check when searching.
//     * @param mixed   $find     The value to find when searching.
//     * @param mixed[] $newValue The replacement meta-data.
//     * @return void
//     */
//    public function replaceMetaDataValue(string $type, string $field, mixed $find, array $newValue): void
//    {
//        foreach (array_keys($this->stackMetaData) as $frameIndex) {
//            foreach (array_keys($this->stackMetaData[$frameIndex]) as $index) {
//
//                if ($this->stackMetaData[$frameIndex][$index]['type'] != $type) {
//                    continue;
//                }
//
//                if (!array_key_exists($field, $this->stackMetaData[$frameIndex][$index]['value'])) {
//                    continue;
//                }
//
//                if ($this->stackMetaData[$frameIndex][$index]['value'][$field] !== $find) {
//                    continue;
//                }
//
//                $this->stackMetaData[$frameIndex][$index]['value'] = $newValue;
//
//                return;
//            }
//        }
//    }

    /**
     * Find particular existing meta-data throughout the callstack, and replace it/them.
     *
     * @param string         $type                The "type" of meta-data to update.
     * @param integer|string $identifier          The identifier to find.
     * @param string|mixed[] $replacementMetaData The replacement meta-data value.
     * @return void
     */
    public function replaceMetaDataValue(
        string $type,
        int|string $identifier,
        string|array $replacementMetaData,
    ): void {

        foreach (array_keys($this->stackMetaData) as $frameIndex) {
            foreach (array_keys($this->stackMetaData[$frameIndex]) as $index) {

                if ($this->stackMetaData[$frameIndex][$index]['identifier'] !== $identifier) {
                    continue;
                }

                if ($this->stackMetaData[$frameIndex][$index]['type'] != $type) {
                    continue;
                }

                $this->stackMetaData[$frameIndex][$index]['value'] = $replacementMetaData;
            }
        }
    }





    /**
     * Store the current stack, and purge any stack-content that doesn't sit inside it anymore.
     *
     * @param array<integer, mixed[]> $newCallStack The new callstack to store.
     * @return void
     */
    private function replaceCallStack(array $newCallStack): void
    {
        $this->pruneBasedOnRegularCallStack($newCallStack);

        $this->callStack = $newCallStack;
    }





    /**
     * Prune off meta-data based on an exception callstack.
     *
     * @param array<integer, mixed[]> $exceptionCallStack The callstack to prune against.
     * @return void
     */
    public function pruneBasedOnExceptionCallStack(array $exceptionCallStack): void
    {
        $this->pruneMetaDataFromRemovedFrames($exceptionCallStack, ['file', 'line']);
//        $this->pruneMetaDataThatDontBelongToTheirFrameAnymore($exceptionCallStack);
    }

    /**
     * Prune off meta-data based on a regular callstack.
     *
     * @param array<integer, mixed[]> $newCallStack The callstack to prune against.
     * @return void
     */
    public function pruneBasedOnRegularCallStack(array $newCallStack): void
    {
        $this->pruneMetaDataFromRemovedFrames($newCallStack);
//        $this->pruneMetaDataThatDontBelongToTheirFrameAnymore($newCallStack);
    }



    /**
     * Remove meta-data that should be pruned.
     *
     * @param array<integer, mixed[]> $newCallStack    The new callstack to compare against.
     * @param string[]                $fieldsToCompare The fields from each frame to compare (whole frames are compared
     *                                                 by default).
     * @return void
     */
    private function pruneMetaDataFromRemovedFrames(array $newCallStack, array $fieldsToCompare = []): void
    {
        $prunableFrames = $this->findPrunableFrames($this->callStack, $newCallStack, $fieldsToCompare);

        foreach ($prunableFrames as $frameIndex) {
            unset($this->stackMetaData[$frameIndex]);
        }
    }

    /**
     * Compare two callstacks, and work out which frames from the old stack needs pruning.
     *
     * Returns the frames' indexes.
     *
     * @param array<integer, mixed[]> $oldCallStack    The old stack to compare.
     * @param array<integer, mixed[]> $newCallStack    The new stack to compare.
     * @param string[]                $fieldsToCompare The fields from each frame to compare (whole frames are compared
     *                                                 by default).
     * @return integer[]
     */
    private function findPrunableFrames(array $oldCallStack, array $newCallStack, array $fieldsToCompare = []): array
    {
        $diffPos = count($fieldsToCompare)
            ? $this->findDiffPosCompareFields($oldCallStack, $newCallStack, $fieldsToCompare)
            : $this->findDiffPos($oldCallStack, $newCallStack);

        return array_slice(array_keys($this->callStack), $diffPos, null, true);
    }

    /**
     * Find the first position of the two arrays where their values are different.
     *
     * @param array<integer, mixed[]> $oldStack The old stack to compare.
     * @param array<integer, mixed[]> $newStack The new stack to compare.
     * @return integer
     */
    private function findDiffPos(array $oldStack, array $newStack): int
    {
        if (!count($oldStack)) {
            return 0;
        }

        // find the first frame that's different
        $index = 0;
        foreach ($newStack as $index => $newFrame) {
            if ($newFrame !== ($oldStack[$index] ?? null)) {
                break;
            }
        }

        $newFrame = $newStack[$index] ?? [];
        $oldFrame = $oldStack[$index] ?? [];

        // check what was different…
        // when any of these are different, the frame must be different
        foreach (['file', 'object', 'function', 'class', 'type'] as $field) {
            if (($newFrame[$field] ?? null) !== ($oldFrame[$field] ?? null)) {
                return $index;
            }
        }

        // the line number is the only field left.
        // given that's the only thing that's different, it's likely to be within the same frame
        // (this wouldn't be the case if a frame in the same class called a method (or closure), then from the same line
        // called another method (or closure) in the same class. Unfortunately PHP doesn't differentiate between
        // them in debug_stacktrace())
        return $index + 1;
    }

    /**
     * Find the first position of the two arrays where their values are different. Compares particular keys from each.
     *
     * This method is available so the stack trace taken from an exception (whose frames don't contain "object" fields)
     * can be compared to a debug_backtrace() backtrace.
     *
     * @param array<integer, mixed[]> $oldCallStack    The old stack to compare.
     * @param array<integer, mixed[]> $newCallStack    The new stack to compare.
     * @param string[]                $fieldsToCompare The fields from each frame to compare.
     * @return integer
     */
    private function findDiffPosCompareFields(
        array $oldCallStack,
        array $newCallStack,
        array $fieldsToCompare = [],
    ): int {

        // find the first frame that's different
        $index = 0;
        foreach ($newCallStack as $index => $newFrame) {

            if (!array_key_exists($index, $oldCallStack)) {
                break;
            }
            $oldFrame = $oldCallStack[$index];

            foreach ($fieldsToCompare as $field) {
                if (($newFrame[$field] ?? null) !== ($oldFrame[$field] ?? null)) {
                    break 2;
                }
            }
        }

        // check what was different…
        // when any of these are different, the frame must be different
        $fields = array_intersect(['file', 'object', 'function', 'class', 'type'], $fieldsToCompare);
        foreach ($fields as $field) {
            if (($newFrame[$field] ?? null) !== ($oldFrame[$field] ?? null)) {
                return $index;
            }
        }

        // the line number is the only field left
        // given that's the only thing that's different, it's likely to be within the same frame
        return $index + 1;
    }



//    /**
//     * Check to make sure that each Meta object belongs to a frame with the same object id.
//     *
//     * @param array<integer, mixed[]> $newCallStack The new callstack that will be stored soon.
//     * @return void
//     */
//    private function pruneMetaDataThatDontBelongToTheirFrameAnymore(array $newCallStack): void
//    {
//        foreach (array_keys($this->stackMetaData) as $frameIndex) {
//            foreach (array_keys($this->stackMetaData[$frameIndex]) as $index) {
//
//                /** @var mixed[] $frameData */
//                $frameData = $this->stackMetaData[$frameIndex][$index]['frame'];
//
//                $objectId = $frameData['object']
//                    ?? null;
//                $frameObjectId = $newCallStack[$frameIndex]['object']
//                    ?? null;
//
//                if ($objectId !== $frameObjectId) {
//                    unset($this->stackMetaData[$frameIndex][$index]);
//                }
//            }
//
//            // re-index so the indexes are sequential
//            // so that resolveMetaDataIndexToUse() below doesn't have trouble when determining the next index to use
//            $this->stackMetaData[$frameIndex] = array_values($this->stackMetaData[$frameIndex]);
//        }
//    }





    /**
     * Record some meta-data, at the current point in the stack.
     *
     * @param string              $type       The type of meta-data to save.
     * @param integer|string|null $identifier Required when updating meta-data later (can be shared).
     * @param mixed               $value      The value to save.
     * @param integer             $paramCount The parameter number this context was.
     * @param mixed[]             $frameData  The number of frames to go back, to get the intended caller frame.
     * @return void
     */
    private function recordMetaData(
        string $type,
        int|string|null $identifier,
        mixed $value,
        int $paramCount,
        array $frameData,
    ): void {

        /** @var integer $frameIndex */
        $frameIndex = array_key_last($this->callStack);

        $this->stackMetaData[$frameIndex] ??= [];

        $line = is_int($frameData['line'] ?? null) ? $frameData['line'] : null;

        $index = $this->resolveMetaDataIndexToUse($frameIndex, $type, $line, $paramCount);

        $newMetaData = [
            'type' => $type,
            'identifier' => $identifier,
            'paramCount' => $paramCount,
            'frame' => $frameData,
            'value' => $value,
        ];

        array_splice($this->stackMetaData[$frameIndex], $index, 0, [$newMetaData]);
    }

    /**
     * Determine the position in the stackMetaData array to update.
     *
     * If meta-data was defined before on the same line, it will be updated.
     *
     * This allows for the code inside a loop to update its meta-data, instead of continually adding more.
     *
     * @param integer      $frameIndex The index of the stackContent array.
     * @param string       $type       The type of meta-data to save.
     * @param integer|null $line       The line that made the call.
     * @param integer      $paramCount The parameter number this context was.
     * @return integer
     */
    private function resolveMetaDataIndexToUse(int $frameIndex, string $type, ?int $line, int $paramCount): int
    {
        // search for the same entry from before
        // if found, remove it, and remove other similar ones (ones from subsequent "parameters" on the same line)
        $firstIndex = null;
        foreach ($this->stackMetaData[$frameIndex] as $index => $metaData) {

            if (!$this->looksLikeSameFrame($metaData, $type, $line)) {
                continue;
            }

            // don't remove parameters that came before this one
            if ($metaData['paramCount'] < $paramCount) {
                continue;
            }

            $firstIndex ??= $index;

            // remove this meta-data, and ones after it
            unset($this->stackMetaData[$frameIndex][$index]);
        }

        // the position was found, return that
        if (!is_null($firstIndex)) {
            $this->stackMetaData[$frameIndex] = array_values($this->stackMetaData[$frameIndex]); // re-index
            return $firstIndex;
        }



        // scan for similar ones and pick the correct spot to insert into next to those
        $lastSimilarIndex = null;
        foreach ($this->stackMetaData[$frameIndex] as $index => $metaData) {

            if (!$this->looksLikeSameFrame($metaData, $type, $line)) {
                continue;
            }

            $lastSimilarIndex = $index;
        }

        if (!is_null($lastSimilarIndex)) {
            return $lastSimilarIndex + 1;
        }



        // add it to the end
        return count($this->stackMetaData[$frameIndex]);
    }

    /**
     * Check to see if this a type and line matches an existing meta-data.
     *
     * @param mixed[]      $metaData The existing meta-data to check.
     * @param string       $type     The type of meta-data to search for.
     * @param integer|null $line     The line number the new meta-data is on.
     * @return boolean
     */
    private function looksLikeSameFrame(array $metaData, string $type, ?int $line): bool
    {
        if ($metaData['type'] !== $type) {
            return false;
        }

        // it *has* to be the same file, as it's the same frame in the callstack (already resolved)
//        if (($metaData['frame']['file'] ?? null) !== $file) {
//            return false;
//        }

        if (($metaData['frame']['line'] ?? null) !== $line) {
            return false;
        }

        return true;
    }





    /**
     * Remove existing meta-data from the top of the callstack.
     *
     * @param string[] $types The types of meta-data to remove.
     * @return void
     */
    private function removeMetaDataFromTop(array $types): void
    {
        if (!count($types)) {
            return;
        }

        $lastFrame = max(array_keys($this->callStack));
        if (!array_key_exists($lastFrame, $this->stackMetaData)) {
            return;
        }

        foreach (array_keys($this->stackMetaData[$lastFrame]) as $index) {
            if (in_array($this->stackMetaData[$lastFrame][$index]['type'], $types, true)) {
                unset($this->stackMetaData[$lastFrame][$index]);
            }
        }

        // re-index so the indexes are sequential
        // so that resolveMetaDataIndexToUse() above doesn't have trouble when determining the next index to use
        $this->stackMetaData[$lastFrame] = array_values($this->stackMetaData[$lastFrame]);
    }
}
