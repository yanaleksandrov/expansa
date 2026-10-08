<?php

declare(strict_types=1);

namespace Expansa\Ai;

use Expansa\Ai\Enums\Stage;

/**
 * One step of a manager round, reported while the round runs.
 * The manager reports a step twice: when it starts (`duration` is null) and when it finishes,
 * then `data` holds what the step produced, so the interface can show it at once.
 *
 * Finished step data by stage:
 * - `context`: `tokens`, `truncated`;
 * - `analysis`, `refinement`: `message` for the user, `specification`, `questions`, `missing_extensions`, `tokens`;
 * - `tool`: `name` and `arguments` (known at start), `failed`, `error` on failure, `tokens`, `truncated`;
 * - `generation`: `repair` (0 for the first attempt, known at start), then `message`, `files` (path => bytes),
 *   `tokens`, or `error` when the response was not a file map;
 * - `validation`: `errors`.
 */
final class Step
{
    /**
     * Whether the step has finished.
     *
     * @var bool
     */
    public bool $isDone {
        get => $this->duration !== null;
    }

    /**
     * Stores the stage, its round, and what it produced.
     */
    public function __construct(

        /**
         * Stage of the round.
         */
        public readonly Stage $stage,

        /**
         * Clarification round: 0 for the request, one more after each answer.
         */
        public readonly int $round,

        /**
         * Unix time the step started.
         */
        public readonly int $time,

        /**
         * Values for the user; see the class description.
         *
         * @var array<string, mixed>
         */
        public readonly array $data = [],

        /**
         * Duration in milliseconds, null while the step runs.
         */
        public readonly ?float $duration = null,
    ) {}
}
