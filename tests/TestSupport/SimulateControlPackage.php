<?php

namespace CodeDistortion\ClarityContext\Tests\TestSupport;

use CodeDistortion\ClarityContext\API\ContextAPI;
use CodeDistortion\ClarityContext\API\MetaCallStackAPI;
use CodeDistortion\ClarityContext\Context;
use CodeDistortion\ClarityContext\Settings;
use CodeDistortion\ClarityContext\Support\Framework\Framework;
use CodeDistortion\ClarityContext\Support\InternalSettings;
use CodeDistortion\ClarityContext\Support\Support;
use Exception;
use Throwable;

/**
 * Methods to help interact with Clarity, by simulating steps that the Control package would do.
 */
class SimulateControlPackage
{
    /**
     * Push "call" meta-data (with "known" values) to the global MetaCallStack.
     *
     * @param integer  $catcherObjectId The id of the caller object.
     * @param string[] $known           The "known" issues to add.
     * @param integer  $framesBack      The number of frames to go back.
     * @return void
     */
    public static function pushControlCallMetaHere(
        int $catcherObjectId,
        array $known = [],
        int $framesBack = 0,
    ): void {

        $metaData = [
            'known' => $known,
        ];

        MetaCallStackAPI::pushMetaData(
            InternalSettings::META_DATA_TYPE__CONTROL_CALL,
            $catcherObjectId,
            $metaData,
            $framesBack + 1,
            [InternalSettings::META_DATA_TYPE__CONTROL_CALL],
        );
    }

    /**
     * Push "call" meta-data (with "known" values) to the global MetaCallStack, and generate an exception in the same
     * frame.
     *
     * @param integer  $catcherObjectId The id of the caller object.
     * @param string[] $known           The "known" issues to add.
     * @param integer  $framesBack      The number of frames to go back.
     * @return Throwable
     */
    public static function pushControlCallMetaAndGenerateException(
        int $catcherObjectId,
        array $known = [],
        int $framesBack = 0,
    ): Throwable {

        self::pushControlCallMetaHere($catcherObjectId, $known, $framesBack);

        return new Exception();
    }



    /**
     * Build a context object more easily.
     *
     * @param integer    $catcherObjectId The id of the object that caught the exception.
     * @param Throwable  $e               The exception to use.
     * @param string[]   $channels        The channels to use.
     * @param string     $level           The level to use.
     * @param boolean    $report          Should the exception be reported?.
     * @param boolean    $rethrow         Should the exception be rethrown?.
     * @param mixed|null $default         The default value to use.
     * @return Context
     */
    public static function buildContext(
        int $catcherObjectId,
        Throwable $e,
        array $channels = ['stack'],
        string $level = Settings::REPORTING_LEVEL_ERROR,
        bool $report = true,
        bool $rethrow = false,
        mixed $default = null,
    ): Context {

        $context = new Context(
            $e,
            null,
            Support::getGlobalMetaCallStack(),
            $catcherObjectId,
            [],
            Framework::config()->getProjectRootDir(),
            $channels,
            $level,
            $report,
            $rethrow,
            $default,
        );

        ContextAPI::rememberExceptionContext($e, $context);

        return $context;
    }

    /**
     * Get this class's file path.
     *
     * @return string
     */
    public static function getClassFile(): string
    {
        return __FILE__;
    }
}
