<?php

namespace CodeDistortion\ClarityContext;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextInitialisationException;
use CodeDistortion\ClarityContext\Support\CallStack\CallStack;
use CodeDistortion\ClarityContext\Support\CallStack\Frame;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\CallMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ContextMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionCaughtMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionThrownMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\LastApplicationFrameMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\Meta;
use CodeDistortion\ClarityContext\Support\Framework\Framework;
use CodeDistortion\ClarityContext\Support\InternalSettings;
use CodeDistortion\ClarityContext\Support\MetaCallStack;
use CodeDistortion\ClarityContext\Support\Support;
use Throwable;

/**
 * Provide context details about an exception.
 */
class Context
{
    /** @var integer The version of this Context class - this will only change when the format of this class changes. */
    public const CONTEXT_VERSION = 1;

    /** @var CallStack The CallStack, populated with meta-data. */
    private CallStack $callStack;

    /** @var boolean Whether the callStack has been initialised or not. */
    private bool $callStackInitialised = false;

    /** @var string[] The issue/s the exception is known to belong to. */
    private array $known = [];

    /** @var string[]|null The issue/s the exception is known to belong to, as set in this Context object directly. */
    private ?array $knownSetByContext = null;

    /** @var integer|null Temp storage spot, resolved when building the frames. */
    private ?int $lastApplicationFrameIndex = null;

    /** @var integer|null Temp storage spot, resolved when building the frames. */
    private ?int $exceptionCaughtFrameIndex = null;

    /** @var CallMeta Temp storage spot, resolved when building the frames. */
    private CallMeta $exceptionCaughtByCallMeta;

    /** @var boolean Whether this object contains enough context details that are worth reporting or not. */
    private bool $detailsAreWorthListing;


    /**
     * Constructor.
     *
     * @param Throwable|null               $exception        The exception that occurred.
     * @param array<integer, mixed[]>|null $phpStackTrace    The stack trace to use when there is no exception.
     * @param MetaCallStack                $metaCallStack    The MetaCallStack object, which includes context details.
     * @param integer|null                 $catcherObjectId  The object-id of the Control instance that caught the
     *                                                       exception.
     * @param array<string,string|integer> $traceIdentifiers The trace identifiers.
     * @param string                       $projectRootDir   The project's root directory.
     * @param string[]                     $channels         The channels to log to.
     * @param string|null                  $level            The log reporting level to use.
     * @param boolean                      $report           Whether the exception should be reported or not.
     * @param boolean|callable|Throwable   $rethrow          Whether the exception should be rethrown or not, a closure
     *                                                       to resolve it, or the exception to rethrow itself.
     * @param mixed                        $default          The default value to return.
     */
    public function __construct(
        private ?Throwable $exception,
        private ?array $phpStackTrace,
        private MetaCallStack $metaCallStack,
        private ?int $catcherObjectId,
        private array $traceIdentifiers,
        private string $projectRootDir,
        private array $channels,
        private ?string $level,
        private bool $report,
        private $rethrow,
        private mixed $default,
    ) {
    }



    /**
     * Get the exception that was thrown, that this Context object was built for.
     *
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * Get the call stack.
     *
     * @return CallStack
     */
    public function getCallStack(): CallStack
    {
        $this->initialiseCallStack();

        return clone $this->callStack;
    }

    /**
     * Get the stack trace (same as the call stack, but in reverse).
     *
     * @return CallStack
     */
    public function getStackTrace(): CallStack
    {
        $this->initialiseCallStack();

        $trace = clone $this->callStack;
        $trace->reverse();
        return $trace;
    }

    /**
     * Get the trace identifiers.
     *
     * @return array<string,string|integer>
     */
    public function getTraceIdentifiers(): array
    {
        return $this->traceIdentifiers;
    }

    /**
     * Get the known issues.
     *
     * @return string[]
     */
    public function getKnown(): array
    {
        $this->initialiseCallStack();

        return $this->knownSetByContext
            ?? $this->known;
    }

    /**
     * Check if there are known issues.
     *
     * @return boolean
     */
    public function hasKnown(): bool
    {
        return count($this->getKnown()) > 0;
    }

    /**
     * Get the channels to log to.
     *
     * @return string[]
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Get the reporting level to use.
     *
     * @return string|null
     */
    public function getLevel(): ?string
    {
        return $this->level;
    }

    /**
     * Check whether this exception should be reported or not.
     *
     * @return boolean
     */
    public function getReport(): bool
    {
        return $this->report;
    }

    /**
     * Check whether this exception should be rethrown or not.
     *
     * @return boolean|callable|Throwable
     */
    public function getRethrow(): bool|callable|Throwable
    {
        return $this->rethrow;
    }

    /**
     * Find out if the context details are worth listing.
     *
     * @return boolean
     */
    public function detailsAreWorthListing(): bool
    {
        $this->initialiseCallStack();

        return $this->detailsAreWorthListing;
    }

    /**
     * Get the default value to return.
     *
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }



    /**
     * Specify the trace identifier/s.
     *
     * @param array<string,string|integer> $traceIdentifiers The trace identifier/s.
     * @return $this
     */
    public function setTraceIdentifiers(array $traceIdentifiers): static
    {
        $this->traceIdentifiers = $traceIdentifiers;

        return $this;
    }

    /**
     * Specify issue/s that the exception is known to belong to.
     *
     * @param string|string[] $known     The issue/s this exception is known to belong to.
     * @param string|string[] ...$known2 The issue/s this exception is known to belong to.
     * @return $this
     */
    public function setKnown(string|array $known, string|array ...$known2): static
    {
        /** @var string[] $known */
        $known = Support::normaliseArgs([], func_get_args());
        $this->knownSetByContext = $known;

        return $this;
    }

    /**
     * Specify the channels to log to.
     *
     * Note: This replaces any previous channels.
     *
     * @param string|string[]        $channel     The channel/s to log to.
     * @param array<string|string[]> ...$channel2 The channel/s to log to.
     * @return $this
     */
    public function setChannels(string|array $channel, string|array ...$channel2): self
    {
        /** @var string[] $channels */
        $channels = Support::normaliseArgs([], func_get_args());
        $this->channels = $channels;

        return $this;
    }

    /**
     * Specify the log reporting level.
     *
     * Note: This replaces the previous level.
     *
     * @param string|null $level The log-level to use.
     * @return $this
     */
    public function setLevel(?string $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Set the log reporting level to "debug".
     *
     * @return $this
     */
    public function debug(): static
    {
        $this->level = Settings::REPORTING_LEVEL_DEBUG;

        return $this;
    }

    /**
     * Set the log reporting level to "info".
     *
     * @return $this
     */
    public function info(): static
    {
        $this->level = Settings::REPORTING_LEVEL_INFO;

        return $this;
    }

    /**
     * Set the log reporting level to "notice".
     *
     * @return $this
     */
    public function notice(): static
    {
        $this->level = Settings::REPORTING_LEVEL_NOTICE;

        return $this;
    }

    /**
     * Set the log reporting level to "warning".
     *
     * @return $this
     */
    public function warning(): static
    {
        $this->level = Settings::REPORTING_LEVEL_WARNING;

        return $this;
    }

    /**
     * Set the log reporting level to "error".
     *
     * @return $this
     */
    public function error(): static
    {
        $this->level = Settings::REPORTING_LEVEL_ERROR;

        return $this;
    }

    /**
     * Set the log reporting level to "critical".
     *
     * @return $this
     */
    public function critical(): static
    {
        $this->level = Settings::REPORTING_LEVEL_CRITICAL;

        return $this;
    }

    /**
     * Set the log reporting level to "alert".
     *
     * @return $this
     */
    public function alert(): static
    {
        $this->level = Settings::REPORTING_LEVEL_ALERT;

        return $this;
    }

    /**
     * Set the log reporting level to "emergency".
     *
     * @return $this
     */
    public function emergency(): static
    {
        $this->level = Settings::REPORTING_LEVEL_EMERGENCY;

        return $this;
    }



    /**
     * Specify that this exception should be reported (using the framework's reporting mechanism) or not.
     *
     * Note: This replaces the previous report setting.
     *
     * @param boolean $report Whether to report the exception or not.
     * @return $this
     */
    public function setReport(bool $report = true): self
    {
        $this->report = $report;

        return $this;
    }

    /**
     * Specify that this exception should not be reported (using the framework's reporting mechanism).
     *
     * Note: This replaces the previous report setting.
     *
     * @return $this
     */
    public function dontReport(): self
    {
        $this->report = false;

        return $this;
    }



    /**
     * Specify whether this exception should be re-thrown or not.
     *
     * Note: This replaces the previous rethrow setting.
     *
     * @param boolean|callable|Throwable $rethrow Whether the exception should be rethrown or not, a closure to resolve
     *                                            it, or the exception to rethrow itself.
     * @return $this
     */
    public function setRethrow(bool|callable|Throwable $rethrow = true): self
    {
        $this->rethrow = $rethrow;

        return $this;
    }

    /**
     * Specify that this exception should not be re-thrown.
     *
     * Note: This replaces the previous rethrow setting.
     *
     * @return $this
     */
    public function dontRethrow(): self
    {
        $this->rethrow = false;

        return $this;
    }



    /**
     * Suppress the exception - don't report and don't rethrow it.
     *
     * @return $this
     */
    public function suppress(): self
    {
        $this->report = false;
        $this->rethrow = false;

        return $this;
    }



    /**
     * Specify the default value that should be returned.
     *
     * @param mixed $default The default value to use.
     * @return $this
     */
    public function setDefault(mixed $default): self
    {
        $this->default = $default;

        return $this;
    }





    /**
     * Initialise the CallStack object.
     *
     * @return void
     */
    private function initialiseCallStack(): void
    {
        if ($this->callStackInitialised) {
            return;
        }
        $this->callStackInitialised = true;

        $this->buildCallStack();
    }

    /**
     * Build the CallStack object that will be made available to the caller.
     *
     * @return void
     */
    private function buildCallStack(): void
    {
        if ($this->exception) {
            $callstack = $this->buildExceptionCallStack();
            $this->metaCallStack->pruneBasedOnExceptionCallStack($callstack);
        } else {
            $callstack = $this->buildPHPCallStack();
            $this->metaCallStack->pruneBasedOnRegularCallStack($callstack);
        }

        $this->buildNewCallStack($callstack);
    }

    /**
     * Build a call stack array from the exception's stack trace.
     *
     * @return array<integer, mixed[]>
     */
    private function buildExceptionCallStack(): array
    {
        /** @var Throwable $exception This method is only ever called when the exception exists. */
        $exception = $this->exception;

        $stackTrace = Support::preparePHPStackTrace(
            $exception->getTrace(),
            $exception->getFile(),
            $exception->getLine()
        );

        $stackTrace = Support::pruneLaravelExceptionHandlerFrames($stackTrace);

        return array_reverse($stackTrace);
    }

    /**
     * Build a callstack array based on PHP's current stacktrace.
     *
     * @return array<integer, mixed[]>
     */
    private function buildPHPCallStack(): array
    {
        $stackTrace = Support::preparePHPStackTrace($this->phpStackTrace ?? []);

        return array_reverse($stackTrace);
    }



    /**
     * Build a new CallStack, prepared with the correct frames and Meta objects.
     *
     * @param array<integer, mixed[]> $callstack The exception's callstack.
     * @return void
     */
    private function buildNewCallStack(array $callstack): void
    {
        $this->callStack = new CallStack(
            $this->buildStackFrames($callstack)
        );

        // this is placed below the code above which generates a new callstack,
        // because the callstack is still tracked, and callbacks are still run
        if (!Framework::config()->getEnabled()) {
            return;
        }

        $this->insertLastApplicationFrameMeta();
        $this->insertExceptionThrownMeta();
        $this->insertExceptionCaughtMeta();

        $this->collectAllKnownDetails();
        $this->decideIfDetailsAreWorthListing();
    }



    /**
     * Combine the call stack and meta-data to build the stack-frames.
     *
     * @param array<integer, mixed[]> $callStack The exception's stack trace.
     * @return Frame[]
     */
    private function buildStackFrames(array $callStack): array
    {
        $stackMetaData = $this->metaCallStack->getStackMetaData();

        $frames = [];
        $isEnabled = Framework::config()->getEnabled();
        $count = 0;
        $callMeta = null;
        foreach ($callStack as $index => $frame) {

            $metaDataObjects = [];
            $wasCaughtHere = false;
            if ($isEnabled) {

                $metaDatas = $stackMetaData[$index]
                    ?? [];

                foreach ($metaDatas as $metaData) {

                    $metaDataObject = $this->buildMetaObject($metaData);
                    $metaDataObjects[] = $metaDataObject;

                    if ($metaDataObject instanceof CallMeta) {
                        $wasCaughtHere = $wasCaughtHere || $metaDataObject->wasCaughtHere();
                        if ($wasCaughtHere) {
                            $callMeta = $metaDataObject;
                        }
                    }
                }
            }

            /** @var string $file */
            $file = $frame['file'] ?? '';

            $projectFile = Support::resolveProjectFile($file, $this->projectRootDir);
            $isApplicationFrame = Support::isApplicationFile($projectFile, $this->projectRootDir);

            $frames[] = new Frame(
                $frame,
                $projectFile,
                $metaDataObjects,
                $isApplicationFrame,
                false,
                ++$count == count($callStack),
                false,
                $wasCaughtHere,
            );

            if ($isApplicationFrame) {
                $this->lastApplicationFrameIndex = $index; // store for later when applying the LastApplicationFrameMeta
            }
            if ($wasCaughtHere) {
                $this->exceptionCaughtFrameIndex = $index; // store for later when applying the ExceptionCaughtMeta
                if ($callMeta) {
                    // store for later when applying the ExceptionCaughtMeta
                    $this->exceptionCaughtByCallMeta = $callMeta;
                }
            }
        }

        return $frames;
    }

    /**
     * Build a Meta object from the meta-data stored in the MetaCallStack object.
     *
     * @param array<string, mixed> $metaData The meta-data stored in the MetaCallStack object.
     * @return Meta
     * @throws ClarityContextInitialisationException When the meta-data's type is invalid.
     */
    private function buildMetaObject(array $metaData): Meta
    {
        /** @var string $type */
        $type = $metaData['type'] ?? '';

        /** @var mixed[] $frameData */
        $frameData = $metaData['frame'];

        /** @var string $file */
        $file = $frameData['file'] ?? '';

        $projectFile = Support::resolveProjectFile($file, $this->projectRootDir);

        switch ($type) {

            case InternalSettings::META_DATA_TYPE__CONTEXT:
                /** @var string[] $context */
                $context = $metaData['value'] ?? '';

                return new ContextMeta($frameData, $projectFile, $context);

            case InternalSettings::META_DATA_TYPE__CONTROL_CALL:
                /** @var array<string,mixed> $value */
                $value = $metaData['value'] ?? [];
                /** @var string[] $known */
                $known = $value['known'] ?? [];
                $objectId = $metaData['identifier'] ?? null;
                // can ony "catch" an exception if there is an exception
                $caughtHere = $this->exception && ($objectId === $this->catcherObjectId);

                return new CallMeta($frameData, $projectFile, $caughtHere, $known);

            default:
                throw ClarityContextInitialisationException::invalidMetaType($type);
        }
    }





    /**
     * Mark last application (i.e. non-vendor) frame with a LastApplicationFrameMeta.
     *
     * @return void
     */
    private function insertLastApplicationFrameMeta(): void
    {
        $frameIndex = $this->lastApplicationFrameIndex;
        if (is_null($frameIndex)) {
            return;
        }

        /** @var Frame $frame */
        $frame = $this->callStack[$frameIndex];

        $meta = new LastApplicationFrameMeta($frame->getRawFrameData(), $frame->getProjectFile());

        $this->callStack[$frameIndex] = $frame->buildCopyWithExtraMeta($meta, false, true);
    }

    /**
     * Mark the frame that threw the exception with a ExceptionThrownMeta.
     *
     * @return void
     */
    private function insertExceptionThrownMeta(): void
    {
        if (!$this->exception) {
            return;
        }

        $frameIndex = count($this->callStack) - 1; // pick the last frame

        /** @var Frame $frame */
        $frame = $this->callStack[$frameIndex];

        $meta = new ExceptionThrownMeta($frame->getRawFrameData(), $frame->getProjectFile());

        $this->callStack[$frameIndex] = $frame->buildCopyWithExtraMeta($meta, true, false);
    }

    /**
     * Mark the frame that caught the exception with a ExceptionCaughtMeta.
     *
     * @return void
     */
    private function insertExceptionCaughtMeta(): void
    {
        if (!$this->exception) {
            return;
        }

        $frameIndex = $this->exceptionCaughtFrameIndex;
        if (is_null($frameIndex)) {
            return;
        }

        /** @var Frame $frame */
        $frame = $this->callStack[$frameIndex];

        // ensure that the ExceptionCaughtMeta looks like it was caught by the CallMeta - i.e. same file + line
        $frameData = $frame->getRawFrameData();
        $frameData['line'] = $this->exceptionCaughtByCallMeta->getLine();

        $meta = new ExceptionCaughtMeta($frameData, $frame->getProjectFile());

        $this->callStack[$frameIndex] = $frame->buildCopyWithExtraMeta($meta, false, false);
    }





    /**
     * Loop through the Meta objects, pick out the "known" details, and store for later.
     *
     * @return void
     */
    private function collectAllKnownDetails(): void
    {
        $known = [];
        /** @var CallMeta $meta */
        foreach ($this->callStack->getMeta(CallMeta::class) as $meta) {
            $known = array_merge($known, $meta->getKnown());
        }

        $this->known = $known;
    }

    /**
     * Inspect the Meta objects, and decide if this Context object is worth reporting.
     *
     * (i.e. more than the "exception" and the "last application frame").
     *
     * @return void
     */
    private function decideIfDetailsAreWorthListing(): void
    {
        $metaTypeCounts = [];
        foreach ($this->callStack->getMeta() as $meta) {
            $metaTypeCounts[get_class($meta)] ??= 0;
            $metaTypeCounts[get_class($meta)]++;
        }

        $this->detailsAreWorthListing = Support::decideIfMetaCountsAreWorthListing($metaTypeCounts);
    }
}
