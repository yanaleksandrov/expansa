<?php

declare(strict_types=1);

namespace Expansa\Ai;

use Expansa\Ai\Enums\Status;

/**
 * A background generation process: the user's request, its state, and the latest draft.
 * One task covers the whole conversation: every clarification answer queues it again.
 */
final class Task
{
    /**
     * Stores the task state; `Queue` changes it, a `Store` persists it.
     */
    public function __construct(

        /**
         * Unique task id, safe for file names and URLs.
         */
        public readonly string $id,

        /**
         * User's natural language request.
         */
        public readonly string $input,

        /**
         * Who created the task, e.g. a user id, to list and check access.
         */
        public readonly string $owner = '',

        /**
         * Current state.
         */
        public Status $status = Status::Queued,

        /**
         * Answer waiting for the worker, null when none is pending.
         */
        public ?string $answer = null,

        /**
         * Result of the latest finished round, null before the first one.
         */
        public ?Draft $draft = null,

        /**
         * Message of the latest failure, empty when there was none.
         */
        public string $error = '',

        /**
         * Worker runs of the current round, for the retry limit.
         */
        public int $attempts = 0,

        /**
         * Unix time the task was created.
         */
        public readonly int $createdAt = 0,

        /**
         * Unix time of the latest change; a running task uses it to detect a dead worker.
         */
        public int $updatedAt = 0,
    ) {}
}
