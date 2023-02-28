<?php

namespace CodeDistortion\ClarityContext\Support;

use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\ExceptionThrownMeta;
use CodeDistortion\ClarityContext\Support\CallStack\MetaData\LastApplicationFrameMeta;
use CodeDistortion\ClarityContext\Support\Framework\Framework;

/**
 * Common methods, shared throughout Clarity Context.
 */
class Support
{
    /**
     * Loop through the arguments, and normalise them into a single array, merged with previously existing values.
     *
     * @internal
     *
     * @param mixed[] $previous The values that were set previously, to be merged into.
     * @param mixed[] $args     The arguments that were passed to the method that called this one.
     * @return mixed[]
     */
    public static function normaliseArgs(array $previous, array $args): array
    {
        foreach ($args as $arg) {
            $arg = is_array($arg)
                ? $arg
                : [$arg];
            $previous = array_merge($previous, $arg);
        }
        return array_values(
            array_unique(
                array_filter($previous),
                SORT_REGULAR
            )
        );
    }

    /**
     * Resolve the path of the file, relative to the project root.
     *
     * @internal
     *
     * @param string $file           The file that made the call.
     * @param string $projectRootDir The root directory of the project.
     * @return string
     */
    public static function resolveProjectFile(string $file, string $projectRootDir): string
    {
        if ($projectRootDir === '') {
            return $file;
        }

        $projectRootDir = rtrim($projectRootDir, DIRECTORY_SEPARATOR);

        return str_starts_with($file, $projectRootDir . DIRECTORY_SEPARATOR)
            ? mb_substr($file, mb_strlen($projectRootDir))
            : $file;
    }

    /**
     * Work out if this is an application (i.e. non-vendor) frame or not.
     *
     * @internal
     *
     * @param string $projectFile    The path of the file, relative to the project root.
     * @param string $projectRootDir The root directory of the project.
     * @return boolean
     */
    public static function isApplicationFile(string $projectFile, string $projectRootDir): bool
    {
        // vendor files cannot be resolved when there's no project root
        if ($projectRootDir === '') {
            return true;
        }

        $vendorDir = DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR;
        return !str_starts_with($projectFile, $vendorDir);
    }

    /**
     * Decide if this the counts of each type of Meta class seem to be worth reporting.
     *
     * @internal
     *
     * (i.e. more than the "exception" and the "last application frame").
     *
     * @param array<string,integer> $metaTypeCounts The counts of each type of Meta class.
     * @return boolean
     */
    public static function decideIfMetaCountsAreWorthListing(array $metaTypeCounts): bool
    {
        ksort($metaTypeCounts);

        $notWorthReporting = [];
        $notWorthReporting[] = [];
        $notWorthReporting[] = [LastApplicationFrameMeta::class => 1];
        $notWorthReporting[] = [ExceptionThrownMeta::class => 1];
        $notWorthReporting[] = [ExceptionThrownMeta::class => 1, LastApplicationFrameMeta::class => 1];

        return !in_array($metaTypeCounts, $notWorthReporting, true);
    }



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



    /**
     * Remove x number of the top-most stack frames, so the intended caller frame is at the "top".
     *
     * @internal
     *
     * @param array<integer, mixed[]> $phpStackTrace The stack trace to alter.
     * @param integer                 $framesBack    The number of frames to go back, to get the intended caller frame.
     * @return array<integer, mixed[]>
     * @throws ClarityContextRuntimeException When an incorrect number of $framesBack is given.
     */
    public static function stepBackStackTrace(array $phpStackTrace, int $framesBack): array
    {
        if ($framesBack < 0) {
            throw ClarityContextRuntimeException::invalidFramesBack($framesBack);
        }

        if ($framesBack >= count($phpStackTrace)) {
            throw ClarityContextRuntimeException::tooManyFramesBack($framesBack, count($phpStackTrace));
        }

        // go back x number of steps
        //
        // @infection-ignore-all - LessThanNegotiation & Increment - prevents timeout - of the loop below
        for ($count = 0; $count < $framesBack; $count++) {
            array_shift($phpStackTrace);
        }

        return $phpStackTrace;
    }

    /**
     * Resolve the current PHP callstack. Then tweak it, so it's in a format that's good for comparing.
     *
     * @internal
     *
     * @param array<integer, mixed[]> $phpStackTrace The stack trace to alter.
     * @param string|null             $file          The first file to shift onto the beginning.
     * @param integer|null            $line          The first line to shift onto the beginning.
     * @return array<integer, mixed[]>
     */
    public static function preparePHPStackTrace(
        array $phpStackTrace,
        ?string $file = null,
        ?int $line = null
    ): array {

        // shift the file and line values by 1 frame
        $newStackTrace = [];
        foreach ($phpStackTrace as $frame) {

            $nextFile = $frame['file'] ?? '';
            $nextLine = $frame['line'] ?? 0;

            $frame['file'] = $file;
            $frame['line'] = $line;
            $newStackTrace[] = $frame;

            $file = $nextFile;
            $line = $nextLine;
        }

        $newStackTrace[] = [
            'file' => $file,
            'line' => $line,
            'function' => '[top]',
            'args' => [],
        ];

        // a very edge case…
        //
        // e.g. call_user_func_array([new Context(), 'add'], ['something']);
        //
        // when Context methods are called via call_user_func_array(..), the callstack's most recent frame is an extra
        // frame that's missing the "file" and "line" keys
        //
        // this causes clarity not to remember meta-data, because it's associated to a "phantom" frame that's forgotten
        // the moment the callstack is inspected next
        //
        // skipping this frame brings the most recent frame back to the place where call_user_func_array was called
        //
        // @infection-ignore-all - FunctionCallRemoval - prevents timeout of array_shift(..) below
        while (
            (count($newStackTrace))
            && (($newStackTrace[0]['file'] == '') || ($newStackTrace[0]['line'] == 0))
        ) {
            array_shift($newStackTrace);
        }

        // turn objects into spl_object_ids
        // - so we're not unnecessarily holding on to references to these objects (in case that matters for the caller),
        // - and to reduce memory requirements
        foreach ($newStackTrace as $index => $step) {

            $object = $step['object']
                ?? null;

            if (is_object($object)) {
                $newStackTrace[$index]['object'] = spl_object_id($step['object']);
            }

            // remove the args, as they can cause unnecessary memory usage during runs of the test-suite
            // this happens when there are a lot of tests, as phpunit can pass large arrays of arg values
            unset($newStackTrace[$index]['args']);
        }

        return $newStackTrace;
    }

    /**
     * Remove Laravel's exception handler methods from the top of the stack trace.
     *
     * @internal
     *
     * @param array<integer, mixed[]> $stackTrace The stack trace to alter.
     * @return array<integer, mixed[]>
     */
    public static function pruneLaravelExceptionHandlerFrames(array $stackTrace): array
    {
        if (!count($stackTrace)) {
            return [];
        }

        $class = is_string($stackTrace[0]['class'] ?? null) ? $stackTrace[0]['class'] : '';

        while (str_starts_with($class, 'Illuminate\Foundation\Bootstrap\HandleExceptions')) {
            array_shift($stackTrace);

            $class = is_string($stackTrace[0]['class'] ?? null) ? $stackTrace[0]['class'] : '';
        }

        return $stackTrace;
    }
}
