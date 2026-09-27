<?php

declare(strict_types=1);

namespace Expansa\Ai\Internal;

/**
 * Execution steps, usage, and flags of one manager round.
 *
 * @internal
 */
final class Trace
{
    /**
     * Step records in execution order.
     *
     * @var array<int, array<string, mixed>>
     */
    public private(set) array $steps = [];

    /**
     * Input tokens of this round.
     *
     * @var int
     */
    public private(set) int $inputTokens = 0;

    /**
     * Output tokens of this round.
     *
     * @var int
     */
    public private(set) int $outputTokens = 0;

    /**
     * Provider requests of this round, including the generator's.
     *
     * @var int
     */
    public private(set) int $providerCalls = 0;

    /**
     * Tool calls of this round.
     *
     * @var int
     */
    public private(set) int $toolCalls = 0;

    /**
     * Size of the bounded context.
     *
     * @var int
     */
    public int $contextTokens = 0;

    /**
     * Repair attempts after the first generation.
     *
     * @var int
     */
    public int $repairs = 0;

    /**
     * Whether the model requested more tool calls than allowed.
     *
     * @var bool
     */
    public bool $toolCallsLimited = false;

    /**
     * Questions dropped because no clarification rounds were left.
     *
     * @var int
     */
    public int $questionsIgnored = 0;

    /**
     * Whether repairs stopped on the total token budget.
     *
     * @var bool
     */
    public bool $budgetExceeded = false;

    /**
     * Tokens of the whole session: earlier rounds and this one.
     *
     * @var int
     */
    public int $total {
        get => $this->spent + $this->inputTokens + $this->outputTokens;
    }

    /**
     * Monotonic start time of the round.
     *
     * @var int
     */
    public readonly int $started;

    /**
     * Starts the round clock.
     */
    public function __construct(

        /**
         * Tokens spent by earlier rounds of the session.
         */
        public readonly int $spent,
    ) {
        $this->started = hrtime(true);
    }

    /**
     * Records one step with its duration and usage.
     *
     * @param string $step Step name: context, analysis, tool, refinement, generation, validation
     * @param int $started Monotonic start time of the step
     * @param array<string, mixed> $details Step-specific values
     * @param int $inputTokens Input tokens used by the step
     * @param int $outputTokens Output tokens used by the step
     * @param int $providerCalls Provider requests made by the step
     */
    public function add(
        string $step,
        int $started,
        array $details = [],
        int $inputTokens = 0,
        int $outputTokens = 0,
        int $providerCalls = 0,
    ): void {
        $this->inputTokens += $inputTokens;
        $this->outputTokens += $outputTokens;
        $this->providerCalls += $providerCalls;
        $this->toolCalls += $step === 'tool' ? 1 : 0;

        $usage = $providerCalls > 0
            ? ['input_tokens' => $inputTokens, 'output_tokens' => $outputTokens, 'provider_calls' => $providerCalls]
            : [];
        $this->steps[] = ['step' => $step, 'duration_ms' => self::elapsed($started), ...$usage, ...$details];
    }

    /**
     * Returns the round summary stored in the session history.
     *
     * @param int $clarifications Answers given before this round
     * @return array<string, mixed> Usage and flags of the round
     */
    public function summary(int $clarifications): array
    {
        return [
            'input_tokens'       => $this->inputTokens,
            'output_tokens'      => $this->outputTokens,
            'total_tokens'       => $this->inputTokens + $this->outputTokens,
            'context_tokens'     => $this->contextTokens,
            'provider_calls'     => $this->providerCalls,
            'tool_calls'         => $this->toolCalls,
            'clarifications'     => $clarifications,
            'repairs'            => $this->repairs,
            'tool_calls_limited' => $this->toolCallsLimited,
            'questions_ignored'  => $this->questionsIgnored,
            'budget_exceeded'    => $this->budgetExceeded,
            'duration_ms'        => self::elapsed($this->started),
        ];
    }

    /**
     * Measures milliseconds since a monotonic timestamp, rounded to three decimals.
     *
     * @param int $since Value of `hrtime(true)`
     */
    public static function elapsed(int $since): float
    {
        return round((hrtime(true) - $since) / 1_000_000, 3);
    }
}
