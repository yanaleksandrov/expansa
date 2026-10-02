<?php

declare(strict_types=1);

namespace Expansa\Ai;

use Expansa\Ai\Exceptions\InvalidResponse;

/**
 * A model response with reported usage and provider-specific metadata.
 */
final class Completion
{
    /**
     * Stores one AI response and the provider's usage report.
     * Token counts should come from the provider response when available.
     *
     * @throws InvalidResponse When the provider reports negative token usage
     */
    public function __construct(

        /**
         * Text returned by the model for the supplied prompt.
         * It contains plain text or a package-requested JSON payload.
         */
        public readonly string $text,

        /**
         * Number of input tokens reported by the provider.
         * This count includes prompt instructions and attached context.
         */
        public int $inputTokens {
            set => $value >= 0 ? $value : throw new InvalidResponse('The provider returned negative input token usage.');
        },

        /**
         * Number of output tokens reported by the provider.
         * This count covers the complete response text.
         */
        public int $outputTokens {
            set => $value >= 0 ? $value : throw new InvalidResponse('The provider returned negative output token usage.');
        },

        /**
         * Provider identifiers, request IDs, and other service data.
         *
         * @var array<string, mixed>
         */
        public readonly array $metadata = [],
    ) {}
}
