<?php

namespace CodeDistortion\ClarityContext\API;

use CodeDistortion\ClarityContext\Context;
use CodeDistortion\ClarityContext\Exceptions\ClarityContextInitialisationException;
use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Support\Framework\Framework;
use CodeDistortion\ClarityContext\Support\InternalSettings;
use CodeDistortion\ClarityContext\Support\Support;
use Throwable;

/**
 * Builds + records Context objects, and retrieves them.
 */
class ContextAPI
{
    /**
     * Build a new Context object based on the current call stack (i.e. not based on an exception).
     *
     * @param integer $framesBack The number of frames to go back.
     * @return Context
     * @throws ClarityContextRuntimeException When an invalid number of steps to go back is given.
     */
    public static function buildContextHere(int $framesBack = 0): Context
    {
        if ($framesBack < 0) {
            throw ClarityContextRuntimeException::invalidFramesBack($framesBack);
        }

        $phpStackTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
        $phpStackTrace = Support::stepBackStackTrace($phpStackTrace, $framesBack);

        return new Context(
            null,
            $phpStackTrace,
            Support::getGlobalMetaCallStack(),
            null,
            DataAPI::getTraceIdentifiers(),
            Framework::config()->getProjectRootDir(),
            Framework::config()->pickBestChannels(false),
            null, // the level will be decided by whatever is using ClarityContext
            false,
            false,
            null,
        );
    }



    /**
     * Build a context object based on an exception, populating the settings that can't be updated later.
     *
     * @param Throwable    $e               The exception that occurred.
     * @param boolean      $isKnown         Whether the exception has "known" values or not.
     * @param integer|null $catcherObjectId The object-id of the Clarity instance that caught the exception.
     * @return Context
     * @throws ClarityContextInitialisationException When an invalid default reporting level is specified in the config.
     */
    public static function buildContextFromException(
        Throwable $e,
        bool $isKnown = false,
        ?int $catcherObjectId = null,
    ): Context {

        $report = Framework::config()->getReport()
            ?? true;

        $context = new Context(
            $e,
            null,
            Support::getGlobalMetaCallStack(),
            $catcherObjectId,
            DataAPI::getTraceIdentifiers(),
            Framework::config()->getProjectRootDir(),
            Framework::config()->pickBestChannels($isKnown),
            Framework::config()->pickBestLevel($isKnown),
            $report,
            false,
            null,
        );

        self::rememberExceptionContext($e, $context);

        return $context;
    }



    /**
     * Associate a Context object to an exception.
     *
     * @param Throwable $exception The exception to associate to.
     * @param Context   $context   The context to associate.
     * @return void
     */
    public static function rememberExceptionContext(Throwable $exception, Context $context): void
    {
        $objectId = spl_object_id($exception);

        $contexts = self::getGlobalExceptionContexts();
        $contexts[$objectId] = $context;

        self::setGlobalExceptionContexts($contexts);
    }

    /**
     * Retrieve an exception's Context object (won't create one when not found).
     *
     * @param Throwable $exception The exception to associate to.
     * @return Context|null
     */
    public static function getRememberedExceptionContext(Throwable $exception): ?Context
    {
        $objectId = spl_object_id($exception);

        return self::getGlobalExceptionContexts()[$objectId]
            ?? null;
    }

    /**
     * Retrieve the Context object that was associated to an exception most recently.
     *
     * @return Context|null
     */
    public static function getLatestExceptionContext(): ?Context
    {
        $contexts = self::getGlobalExceptionContexts();
        $objectIds = array_keys($contexts);
        $objectId = end($objectIds);
        return $contexts[$objectId]
            ?? null;
    }

    /**
     * Forget an exception's Context object.
     *
     * @param Throwable $exception The exception to associate to.
     * @return void
     */
    public static function forgetExceptionContext(Throwable $exception): void
    {
        $objectId = spl_object_id($exception);

        $contexts = self::getGlobalExceptionContexts();
        unset($contexts[$objectId]);

        self::setGlobalExceptionContexts($contexts);
    }





    /**
     * Get the current exception-Contexts associations from global storage.
     *
     * @return array<integer, Context>
     */
    private static function getGlobalExceptionContexts(): array
    {
        /** @var array<integer, Context> $return */
        $return = Framework::depInj()->get(InternalSettings::CONTAINER_KEY__EXCEPTION_CONTEXTS, []);
        return $return;
    }

    /**
     * Set the exception-Contexts associations in global storage.
     *
     * @param array<integer, Context> $contexts The stack of Context objects to store.
     * @return void
     */
    private static function setGlobalExceptionContexts(array $contexts): void
    {
        Framework::depInj()->set(InternalSettings::CONTAINER_KEY__EXCEPTION_CONTEXTS, $contexts);
    }
}
