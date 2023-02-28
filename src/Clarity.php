<?php

namespace CodeDistortion\ClarityContext;

use CodeDistortion\ClarityContext\API\ContextAPI;
use CodeDistortion\ClarityContext\API\DataAPI;
use CodeDistortion\ClarityContext\API\MetaCallStackAPI;
use CodeDistortion\ClarityContext\Exceptions\ClarityContextInitialisationException;
use CodeDistortion\ClarityContext\Exceptions\ClarityContextRuntimeException;
use CodeDistortion\ClarityContext\Support\InternalSettings;
use Throwable;

/**
 * Let the caller add context details, and retrieve context objects.
 */
class Clarity
{
    /**
     * Add context details, to be reported later if needed.
     *
     * @param string|mixed[] $context     Context details about what's currently going on.
     * @param string|mixed[] ...$context2 Context details about what's currently going on.
     * @return void
     */
    public static function context(string|array $context, string|array ...$context2): void
    {
        /** @var array<string|mixed[]> $args */
        $args = func_get_args();
        MetaCallStackAPI::pushMultipleMetaData(
            InternalSettings::META_DATA_TYPE__CONTEXT,
            null,
            $args,
            1,
            [InternalSettings::META_DATA_TYPE__CONTROL_CALL],
        );
    }



    /**
     * Build a new Context object based on the current call stack (not based on an exception).
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

        return ContextAPI::buildContextHere($framesBack + 1);
    }



    /**
     * Retrieve an exception's Context object (will create one when not found).
     *
     * Intended to be used by the framework's exception handler.
     *
     * @param Throwable $exception The exception to fetch the Context for.
     * @return Context
     * @throws ClarityContextInitialisationException When an invalid default reporting level is specified in the config.
     */
    public static function getExceptionContext(Throwable $exception): Context
    {
        return ContextAPI::getRememberedExceptionContext($exception)
            ?? ContextAPI::buildContextFromException($exception); // build new based on the exception
    }



    /**
     * Specify a trace identifier, for tracing the current request.
     *
     * (Multiple can be set with different names).
     *
     * @param string|integer $id   The identifier to use.
     * @param string|null    $name An optional name for the identifier.
     * @return void
     */
    public static function traceIdentifier(string|int $id, string $name = null): void
    {
        DataAPI::traceIdentifier($id, $name);
    }
}
