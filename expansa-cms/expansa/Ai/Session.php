<?php

declare(strict_types=1);

namespace Expansa\Ai;

/**
 * State of one extension request between clarification rounds.
 * The manager keeps no state: the application stores the session (for example with `serialize()`)
 * and passes it back to `Manager::clarify()` together with the user's answer.
 * Each round returns a new session, so a failed round leaves the previous one usable.
 */
final class Session
{
    /**
     * Number of answers already given.
     *
     * @var int
     */
    public int $clarifications {
        get => count($this->answers);
    }

    /**
     * Tokens spent by all finished rounds.
     *
     * @var int
     */
    public int $spent {
        get => (int) array_sum(array_column($this->history, 'total_tokens'));
    }

    /**
     * Stores the request, answers, pending questions, and round summaries.
     */
    public function __construct(

        /**
         * Original user request.
         */
        public readonly string $input,

        /**
         * User answers in the order they were given.
         *
         * @var string[]
         */
        public readonly array $answers = [],

        /**
         * Questions the latest round asked, empty when nothing is pending.
         *
         * @var string[]
         */
        public readonly array $questions = [],

        /**
         * Usage summaries of the finished rounds.
         *
         * @var array<int, array<string, mixed>>
         */
        public readonly array $history = [],
    ) {}

    /**
     * Returns a copy with one more answer and no pending questions.
     *
     * @param string $answer User's response to the pending questions
     */
    public function withAnswer(string $answer): self
    {
        return new self($this->input, [...$this->answers, $answer], [], $this->history);
    }

    /**
     * Returns a copy with the finished round's questions and usage summary.
     *
     * @param string[] $questions Questions for the user, empty when the round produced files
     * @param array<string, mixed> $summary Usage summary of the round
     */
    public function withRound(array $questions, array $summary): self
    {
        return new self($this->input, $this->answers, $questions, [...$this->history, $summary]);
    }
}
