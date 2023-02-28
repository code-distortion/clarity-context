<?php

namespace CodeDistortion\ClarityContext\Support;

/**
 * Common values, shared throughout Clarity Context.
 */
abstract class InternalSettings
{
    // keys used to store things in the framework's data store

    /** @var string The key used to store the MetaCallStack inside the framework's service container. */
    public const CONTAINER_KEY__META_CALL_STACK = 'code-distortion/clarity-context/meta-call-stack';

    /** @var string The key used to store the Contexts that have been associated with exceptions. */
    public const CONTAINER_KEY__EXCEPTION_CONTEXTS = 'code-distortion/clarity-context/exception-contexts';

    /** @var string The key used to store data inside the framework's service container. */
    public const CONTAINER_KEY__DATA_STORE = 'code-distortion/clarity-context/data-store';



    // the names of meta-data types

    /** @var string The "context" meta-data type name. */
    public const META_DATA_TYPE__CONTEXT = 'context-data';

    /** @var string The "context" meta-data type name. */
    public const META_DATA_TYPE__CONTROL_CALL = 'clarity-context-call';



    // Laravel specific settings

    /** @var string The Clarity Context config file that gets published. */
    public const LARAVEL_CONTEXT__CONFIG_PATH = '/config/context.config.php';

    /** @var string The name of the Clarity Context config file. */
    public const LARAVEL_CONTEXT__CONFIG_NAME = 'code_distortion.clarity_context';



    /** @var string The name of the Clarity Control config file. */
    public const LARAVEL_CONTROL__CONFIG_NAME = 'code_distortion.clarity_control';
}
