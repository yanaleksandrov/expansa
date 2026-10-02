<?php

declare(strict_types=1);

namespace Expansa\Ai;

/**
 * A provider-independent request: stable instructions, request data, and the expected response shape.
 * Providers map `system` and `user` to their message roles; the stable system part suits prompt caching.
 * A non-null `schema` asks for structured output, which providers should pass to their JSON mode.
 */
final class Prompt
{
    /**
     * Stores one prompt for a single provider call.
     */
    public function __construct(

        /**
         * Task instructions shared by every call of the same stage.
         *
         * @var string
         */
        public readonly string $system,

        /**
         * Request data for this call, encoded as JSON.
         *
         * @var string
         */
        public readonly string $user,

        /**
         * JSON Schema of the expected response, or null for free text.
         *
         * @var array<string, mixed>|null
         */
        public readonly ?array $schema = null,
    ) {}
}
