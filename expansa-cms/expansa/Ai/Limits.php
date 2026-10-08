<?php

declare(strict_types=1);

namespace Expansa\Ai;

use Expansa\Ai\Exceptions\InvalidConfiguration;

/**
 * Configurable limits for one extension generation and clarification session.
 */
final class Limits
{
    /**
     * Defines hard per-generation limits for context, model calls, and repair.
     * Zero disables the corresponding optional operation.
     *
     * @throws InvalidConfiguration When a limit is outside its allowed range
     */
    public function __construct(

        /**
         * Maximum token count accepted for the request and follow-up answers.
         */
        public int $inputTokens = 4000 {
            set => $value > 0 ? $value : throw new InvalidConfiguration('Input token limit must be positive.');
        },

        /**
         * Maximum context size sent to the provider, in tokens.
         * Context sources receive this limit before returning results.
         */
        public int $contextTokens = 6000 {
            set => $value >= 0 ? $value : throw new InvalidConfiguration('Context token limit cannot be negative.');
        },

        /**
         * Maximum requested model output size for each provider call.
         * The selected provider should pass it to its service API.
         */
        public int $outputTokens = 1500 {
            set => $value > 0 ? $value : throw new InvalidConfiguration('Output token limit must be positive.');
        },

        /**
         * Maximum tool calls accepted from one specification response.
         * Additional calls are ignored and reported in result metadata.
         */
        public int $toolCalls = 3 {
            set => $value >= 0 ? $value : throw new InvalidConfiguration('Tool call limit cannot be negative.');
        },

        /**
         * Maximum tokens retained from each tool result.
         * Longer results are truncated before they reach the model.
         */
        public int $toolResultTokens = 1500 {
            set => $value >= 0 ? $value : throw new InvalidConfiguration('Tool result token limit cannot be negative.');
        },

        /**
         * Maximum user clarification rounds for one extension request.
         * Further answers are rejected after this limit is reached.
         */
        public int $clarifications = 2 {
            set => $value >= 0 ? $value : throw new InvalidConfiguration('Clarification limit cannot be negative.');
        },

        /**
         * Maximum regeneration attempts after validation failures.
         * The initial generation is not counted as a repair attempt.
         */
        public int $repairs = 2 {
            set => $value >= 0 ? $value : throw new InvalidConfiguration('Repair limit cannot be negative.');
        },

        /**
         * Maximum tokens spent by the whole session, all rounds and repairs included.
         * Checked before each provider call: repairs stop, other calls throw `BudgetExceeded`.
         */
        public int $totalTokens = 60000 {
            set => $value > 0 ? $value : throw new InvalidConfiguration('Total token limit must be positive.');
        },
    ) {}
}
