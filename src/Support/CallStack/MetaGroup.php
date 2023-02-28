<?php

namespace CodeDistortion\ClarityContext\Support\CallStack;

use CodeDistortion\ClarityContext\Support\CallStack\MetaData\Meta;

/**
 * A group of Meta objects, grouped based on their location (frame and line number).
 */
class MetaGroup
{
    /**
     * @param string  $file                       The frame's file.
     * @param string  $projectFile                The frame's file, relative to the project-root.
     * @param integer $line                       The frame's line.
     * @param string  $function                   The frame's function.
     * @param string  $class                      The frame's class.
     * @param string  $type                       The frame's type.
     * @param Meta[]  $meta                       The Meta objects in this group.
     * @param boolean $isInApplicationFrame       Whether this meta-group is in an application (i.e. non-vendor) frame
     *                                            or not.
     * @param boolean $isInLastApplicationFrame   Whether this meta-group is the last application (i.e. non-vendor)
     *                                            frame or not.
     * @param boolean $isInLastFrame              Whether this meta-group is in the last frame or not.
     * @param boolean $exceptionThrownInThisFrame Whether the exception was thrown in the frame this meta-group is from
     *                                            or not.
     * @param boolean $exceptionCaughtInThisFrame Whether the exception was caught in the frame this meta-group is from
     *                                            or not.
     */
    public function __construct(
        private string $file,
        private string $projectFile,
        private int $line,
        private string $function,
        private string $class,
        private string $type,
        private array $meta,
        private bool $isInApplicationFrame,
        private bool $isInLastApplicationFrame,
        private bool $isInLastFrame,
        private bool $exceptionThrownInThisFrame,
        private bool $exceptionCaughtInThisFrame,
    ) {
    }

    /**
     * Constructor that populates its details from a Frame object.
     *
     * @param Frame  $frame       The frame to populate details from.
     * @param Meta   $firstMeta   The group's first Meta object.
     * @param Meta[] $metaObjects The Meta objects in this group.
     * @return self
     */
    public static function newFromFrameAndMeta(Frame $frame, Meta $firstMeta, array $metaObjects): self
    {
        return new MetaGroup(
            $firstMeta->getFile(),
            $firstMeta->getProjectFile(),
            $firstMeta->getLine(),
            $firstMeta->getFunction(),
            $firstMeta->getClass(),
            $firstMeta->getType(),
            $metaObjects,
            $frame->isApplicationFrame(),
            $frame->isLastApplicationFrame(),
            $frame->isLastFrame(),
            $frame->exceptionWasThrownHere(),
            $frame->exceptionWasCaughtHere(),
        );
    }



    /**
     * Get the frame's file.
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Get the frame's file, relative to the project-root.
     *
     * @return string
     */
    public function getProjectFile(): string
    {
        return $this->projectFile;
    }

    /**
     * Get the frame's line.
     *
     * @return integer
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * Get the frame's function.
     *
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Get the frame's class.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

//    /**
//     * Get the frame's object.
//     *
//     * @return object|null
//     */
//    public function getObject(): ?object
//    {
//        return $this->object;
//    }

    /**
     * Get the frame's type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

//    /**
//     * Get the frame's args.
//     *
//     * @return mixed[]|null
//     */
//    public function getArgs(): ?array
//    {
//        return $this->args;
//    }

    /**
     * Get the Meta objects in this group.
     *
     * @return Meta[]
     */
    public function getMeta(): array
    {
        return $this->meta;
    }



    /**
     * Find out if this meta-group is in an application (i.e. non-vendor) frame.
     *
     * @return boolean
     */
    public function isInApplicationFrame(): bool
    {
        return $this->isInApplicationFrame;
    }

    /**
     * Find out if this meta-group is the last application (i.e. non-vendor) frame.
     *
     * @return boolean
     */
    public function isInLastApplicationFrame(): bool
    {
        return $this->isInLastApplicationFrame;
    }

    /**
     * Find out if this meta-group is in a vendor frame.
     *
     * @return boolean
     */
    public function isInVendorFrame(): bool
    {
        return !$this->isInApplicationFrame;
    }

    /**
     * Find out if this meta-group is in the last frame.
     *
     * @return boolean
     */
    public function isInLastFrame(): bool
    {
        return $this->isInLastFrame;
    }

    /**
     * Find out if the exception was thrown in the frame this meta-group is from.
     *
     * @return boolean
     */
    public function exceptionThrownInThisFrame(): bool
    {
        return $this->exceptionThrownInThisFrame;
    }

    /**
     * Find out if the exception was caught in the frame this meta-group is from.
     *
     * @return boolean
     */
    public function exceptionCaughtInThisFrame(): bool
    {
        return $this->exceptionCaughtInThisFrame;
    }
}
