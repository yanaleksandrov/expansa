<?php

declare(strict_types=1);

namespace Expansa\Ai\Internal;

use Closure;
use Expansa\Ai\Enums\Stage;
use Expansa\Ai\Step;

/**
 * Execution steps, usage, and flags of one manager round.
 * Steps run one at a time: `begin()` reports the started step, `end()` records and reports it finished.
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
     * Step that runs now.
     *
     * @var Step
     */
    private Step $step;

    /**
     * Monotonic start time of the running step.
     *
     * @var int
     */
    private int $stepStarted = 0;

    /**
     * Starts the round clock.
     */
    public function __construct(

        /**
         * Tokens spent by earlier rounds of the session.
         */
        public readonly int $spent,

        /**
         * Clarification round reported with the steps.
         */
        public readonly int $round = 0,

        /**
         * Receives each step when it starts and when it finishes; its exceptions stop the round.
         *
         * @var (Closure(Step): void)|null
         */
        private readonly ?Closure $progress = null,
    ) {
        $this->started = hrtime(true);
    }

    /**
     * Starts a step and reports it.
     *
     * @param Stage $stage Stage of the step
     * @param array<string, mixed> $data Values known at the start, kept in the finished step
     */
    public function begin(Stage $stage, array $data = []): void
    {
        $this->step = new Step($stage, $this->round, time(), $data);
        $this->stepStarted = hrtime(true);
        if ($this->progress !== null) {
            ($this->progress)($this->step);
        }
    }

    /**
     * Finishes the running step: records its duration and usage and reports what it produced.
     *
     * @param array<string, mixed> $details Values for result metadata
     * @param array<string, mixed>|null $result Values for the user; null reports `details`
     * @param int $inputTokens Input tokens used by the step
     * @param int $outputTokens Output tokens used by the step
     * @param int $providerCalls Provider requests made by the step
     */
    public function end(
        array $details = [],
        ?array $result = null,
        int $inputTokens = 0,
        int $outputTokens = 0,
        int $providerCalls = 0,
    ): void {
        $stage = $this->step->stage;
        $duration = self::elapsed($this->stepStarted);
        $this->inputTokens += $inputTokens;
        $this->outputTokens += $outputTokens;
        $this->providerCalls += $providerCalls;
        $this->toolCalls += $stage === Stage::Tool ? 1 : 0;

        $usage = $providerCalls > 0
            ? ['input_tokens' => $inputTokens, 'output_tokens' => $outputTokens, 'provider_calls' => $providerCalls]
            : [];
        $this->steps[] = ['step' => $stage->value, 'duration_ms' => $duration, ...$usage, ...$details];

        if ($this->progress !== null) {
            $tokens = $providerCalls > 0 ? ['tokens' => $inputTokens + $outputTokens] : [];
            $data = [...$this->step->data, ...$result ?? $details, ...$tokens];
            ($this->progress)(new Step($stage, $this->round, $this->step->time, $data, $duration));
        }
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
