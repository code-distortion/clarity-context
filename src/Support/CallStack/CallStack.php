<?php

namespace CodeDistortion\ClarityContext\Support\CallStack;

use ArrayAccess;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\Meta;
use CodeDistortion\ClarityContext\Support\Support;
use Countable;
use InvalidArgumentException;
use OutOfBoundsException;
use SeekableIterator;

/**
 * Class to navigate through CallStackFrames.
 *
 * @codingStandardsIgnoreStart
 *
 * @implements SeekableIterator<integer, Frame>
 * @implements ArrayAccess<integer, Frame>
 *
 * @codingStandardsIgnoreEnd
 */
class CallStack implements ArrayAccess, Countable, SeekableIterator
{
    /** @var Frame[] The CallStackFrames to use. */
    private array $frames;

    /** @var integer The current iteration position. */
    private int $pos = 0;

    /** @var boolean Keep track of whether the frames are reversed or not. */
    private bool $isReversed = false;

    /** @var Meta[] The Meta objects contained in these frames. */
    private array $meta;

    /** @var boolean Whether the Meta objects have been collected and cached or not. */
    private bool $initialisedMeta = false;



    /**
     * Constructor.
     *
     * @param Frame[] $stack The Callstack frames.
     */
    public function __construct(array $stack)
    {
        $this->frames = array_values($stack);
    }



    /**
     * Jump to a position.
     *
     * (SeekableIterator interface).
     *
     * @param integer $offset The offset to use.
     * @return void
     * @throws OutOfBoundsException When the offset is invalid.
     */
    public function seek(int $offset): void
    {
        if (!array_key_exists($offset, $this->frames)) {
            throw new OutOfBoundsException("Position $offset does not exist");
        }

        $this->pos = $offset;
    }

    /**
     * Return the current frame.
     *
     * (SeekableIterator interface).
     *
     * @return Frame|null
     */
    public function current(): ?Frame
    {
        return $this->frames[$this->pos]
            ?? null;
    }

    /**
     * Retrieve the current key.
     *
     * (SeekableIterator interface).
     *
     * @return integer
     */
    public function key(): int
    {
        return $this->pos;
    }

    /**
     * Move to the next frame.
     *
     * (SeekableIterator interface).
     *
     * @return void
     */
    public function next(): void
    {
        $this->pos++;
    }

    /**
     * Jump back to the first frame.
     *
     * (SeekableIterator interface).
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->pos = 0;
    }

    /**
     * Check if the current position is valid.
     *
     * (SeekableIterator interface).
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return $this->offsetExists($this->pos);
    }



    /**
     * Check if a position is valid.
     *
     * (ArrayAccess interface).
     *
     * @param mixed $offset The offset to check.
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && array_key_exists($offset, $this->frames);
    }

    /**
     * Retrieve the value at a particular position.
     *
     * (ArrayAccess interface).
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->frames[$offset];
    }

    /**
     * Set the value at a particular position.
     *
     * (ArrayAccess interface).
     *
     * @param mixed $offset The offset to update.
     * @param mixed $value  The value to set.
     * @return void
     * @throws InvalidArgumentException When an invalid value is given.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$value instanceof Frame) {
            throw new InvalidArgumentException("Invalid value - CallStack cannot store this value");
        }

        $this->frames[$offset] = $value;
    }

    /**
     * Remove the value from a particular position.
     *
     * (ArrayAccess interface).
     *
     * @param mixed $offset The offset to remove.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->frames[$offset]);
    }



    /**
     * Retrieve the number of frames.
     *
     * (Countable interface).
     *
     * @return integer
     */
    public function count(): int
    {
        return count($this->frames);
    }



    /**
     * Reverse the callstack (so it looks like a backtrace).
     *
     * Resets the current position afterwards.
     *
     * @return $this
     */
    public function reverse(): self
    {
        $this->frames = array_reverse($this->frames);
        $this->isReversed = !$this->isReversed;
        $this->rewind();

        return $this;
    }

    /**
     * Retrieve the last application (i.e. non-vendor) frame (before the exception was thrown).
     *
     * @return Frame|null
     */
    public function getLastApplicationFrame(): ?Frame
    {
        $frameIndex = $this->getLastApplicationFrameIndex();
        return !is_null($frameIndex)
            ? $this->frames[$frameIndex]
            : null;
    }

    /**
     * Retrieve the index of the last application (i.e. non-vendor) frame (before the exception was thrown).
     *
     * @return integer|null
     */
    public function getLastApplicationFrameIndex(): ?int
    {
        $indexes = array_keys($this->frames);
        foreach ($indexes as $index) {

            if (!$this->frames[$index]->isLastApplicationFrame()) {
                continue;
            }

            return $index;
        }
        return null;
    }

    /**
     * Retrieve the frame that threw the exception.
     *
     * @return Frame|null
     */
    public function getExceptionThrownFrame(): ?Frame
    {
        $frameIndex = $this->getExceptionThrownFrameIndex();
        return !is_null($frameIndex)
            ? $this->frames[$frameIndex]
            : null;
    }

    /**
     * Retrieve the index of the frame that threw the exception.
     *
     * @return integer|null
     */
    public function getExceptionThrownFrameIndex(): ?int
    {
        $indexes = array_keys($this->frames);
        foreach ($indexes as $index) {

            if (!$this->frames[$index]->exceptionWasThrownHere()) {
                continue;
            }

            return $index;
        }
        return null;
    }

    /**
     * Retrieve the frame that caught the exception.
     *
     * @return Frame|null
     */
    public function getExceptionCaughtFrame(): ?Frame
    {
        $frameIndex = $this->getExceptionCaughtFrameIndex();
        return !is_null($frameIndex)
            ? $this->frames[$frameIndex]
            : null;
    }

    /**
     * Retrieve the index of the frame that caught the exception.
     *
     * @return integer|null
     */
    public function getExceptionCaughtFrameIndex(): ?int
    {
        $indexes = array_keys($this->frames);
        foreach ($indexes as $index) {

            if (!$this->frames[$index]->exceptionWasCaughtHere()) {
                continue;
            }

            return $index;
        }
        return null;
    }





    /**
     * Get the Meta objects contained within the frames.
     *
     * @param array<string|string[]> ...$class The type/s of meta-objects to get. Defaults to all meta-objects.
     * @return Meta[]
     */
    public function getMeta(string|array ...$class): array
    {
        $this->cacheAllMeta();

        /** @var string[] $classes */
        $classes = Support::normaliseArgs([], $class);
        if (!count($classes)) {
            return $this->meta;
        }

        $matchingMeta = [];
        foreach ($this->meta as $meta) {
            foreach ($classes as $class) {
                if ($meta instanceof $class) {
                    $matchingMeta[] = $meta;
                    break;
                }
            }
        }

        return $matchingMeta;
    }

    /**
     * Cache the Meta objects contained within the frames.
     *
     * @return void
     */
    private function cacheAllMeta(): void
    {
        if ($this->initialisedMeta) {
            return;
        }
        // @infection-ignore-all - "TrueValue - when switched to false, the meta-objects can be collected again, with
        // the same result"
        $this->initialisedMeta = true;

        $this->meta = [];
        foreach ($this->frames as $frame) {
            foreach ($frame->getMeta() as $meta) {
                $this->meta[] = $meta;
            }
        }
    }



    /**
     * Get the meta-data, grouped nicely into MetaGroup objects.
     *
     * @param array<string|string[]> ...$class The type/s of Meta objects to get. Defaults to all Meta objects.
     * @return MetaGroup[]
     */
    public function getMetaGroups(string|array ...$class): array
    {
        /** @var string[] $classes */
        $classes = Support::normaliseArgs([], $class);

        // process them all in callStack order, reverse the result later on
        $frames = $this->isReversed
            ? array_reverse($this->frames)
            : $this->frames;

        $index = -1;
        $lastFile = $lastLine = null;
        $groupedMetaObjects = $mainFrames = [];
        foreach ($frames as $frame) {

            foreach ($frame->getMeta($classes) as $meta) {

                // work out if a new group of Meta objects should be created
                if (
                    // if the frames are not similar
                    ($lastFile !== $meta->getFile())
                    // or it's not on the same line, or the next
                    // (this ensures that LastApplicationFrameMeta and ExceptionThrownMeta from the frame inside the
                    // callable can be grouped with the code that calls Control. If other frames are on lines this
                    // close,  it's probably ok to group them together for the purposes of the MetaGroup)
                    || (!in_array($meta->getLine(), [$lastLine, $lastLine + 1]))
                ) {
                    // @infection-ignore-all - Increment - the order doesn't matter - a ksort can be added to cause the
                    // it to generate an exception, but I'd prefer not to add unnecessary code just to break a mutation
                    $index++;
                    $lastFile = $meta->getFile();
                }

                $lastLine = $meta->getLine();

                $groupedMetaObjects[$index] ??= [];
                $groupedMetaObjects[$index][] = $meta;
                $mainFrames[$index] = $frame;
            }
        }
//        ksort($groupedMetaObjects); the un-needed ksort that breaks the $index-- mutation above

        $metaGroups = [];
        foreach (array_keys($groupedMetaObjects) as $index) {
            $mainFrame = $mainFrames[$index];
            $firstMeta = $groupedMetaObjects[$index][0];
            $metaGroups[] = MetaGroup::newFromFrameAndMeta($mainFrame, $firstMeta, $groupedMetaObjects[$index]);
        }

        return $this->isReversed
            ? array_reverse($metaGroups)
            : $metaGroups;
    }
}
