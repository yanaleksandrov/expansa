<?php

declare(strict_types=1);

namespace Expansa\Ai;

use Expansa\Ai\Exceptions\InvalidResponse;

/**
 * A generated file map with usage reported by its generator.
 */
final class Generation
{
    /**
     * Stores generated files and usage reported during code generation.
     * Custom generators can return zero usage when they do not call an AI provider.
     *
     * @throws InvalidResponse When the generator reports negative usage
     */
    public function __construct(

        /**
         * Generated extension files keyed by relative paths.
         *
         * @var array<string, string>
         */
        public readonly array $files,

        /**
         * Input tokens used by the generator's provider calls.
         * Custom generators may leave this at zero when not applicable.
         */
        public int $inputTokens = 0 {
            set => $value >= 0 ? $value : throw new InvalidResponse('The generator returned negative input token usage.');
        },

        /**
         * Output tokens used by the generator's provider calls.
         * Custom generators may leave this at zero when not applicable.
         */
        public int $outputTokens = 0 {
            set => $value >= 0 ? $value : throw new InvalidResponse('The generator returned negative output token usage.');
        },

        /**
         * Number of provider requests performed by the generator.
         * Custom generators without AI access should report zero.
         */
        public int $providerCalls = 0 {
            set => $value >= 0 ? $value : throw new InvalidResponse('The generator returned a negative provider call count.');
        },

        /**
         * Additional generator or provider details.
         *
         * @var array<string, mixed>
         */
        public readonly array $metadata = [],
    ) {}
}
