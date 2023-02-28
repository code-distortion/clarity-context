<?php

namespace CodeDistortion\ClarityContext\API;

use CodeDistortion\ClarityContext\Support\Framework\Framework;
use CodeDistortion\ClarityContext\Support\InternalSettings;

/**
 * Methods to store and retrieve data.
 */
class DataAPI
{
    /**
     * Specify a trace identifier, for tracing the current request.
     *
     * (Multiple can be set with different names).
     *
     * @param string|integer $id   The identifier to use.
     * @param string|null    $name An optional name for the identifier.
     * @return void
     */
    public static function traceIdentifier(string|int $id, ?string $name = null): void
    {
        /** @var mixed[] $data */
        $data = Framework::depInj()->get(InternalSettings::CONTAINER_KEY__DATA_STORE, []);

        /** @var array<string,string|integer> $identifiers */
        $identifiers = $data['trace-identifiers'] ?? [];
        $identifiers[(string) $name] = $id;
        $data['trace-identifiers'] = $identifiers;

        Framework::depInj()->set(InternalSettings::CONTAINER_KEY__DATA_STORE, $data);
    }

    /**
     * Retrieve the trace identifiers.
     *
     * @return array<string,string|integer>
     */
    public static function getTraceIdentifiers(): array
    {
        /** @var mixed[] $data */
        $data = Framework::depInj()->get(InternalSettings::CONTAINER_KEY__DATA_STORE, []);

        /** @var array<string,string|integer> $identifiers */
        $identifiers = $data['trace-identifiers'] ?? [];

        return $identifiers;
    }
}
