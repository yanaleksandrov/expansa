<?php

declare(strict_types=1);

namespace Expansa\Ai\Enums;

/**
 * State of a background generation task.
 */
enum Status: string
{
    /**
     * Waiting for a worker: a new request or a submitted answer.
     */
    case Queued = 'queued';

    /**
     * A worker is generating; a task running longer than the queue timeout counts as dead and is retried.
     */
    case Running = 'running';

    /**
     * The model needs answers: pass one to `Queue::clarify()`.
     */
    case Questions = 'questions';

    /**
     * The draft is valid and can be shown to the user for installation.
     */
    case Ready = 'ready';

    /**
     * Files were generated, but validation errors remain after the repairs.
     */
    case Invalid = 'invalid';

    /**
     * The task needs PHP extensions the platform lacks.
     */
    case MissingExtensions = 'missing_extensions';

    /**
     * Generation failed with an error; see `Task::$error`.
     */
    case Failed = 'failed';

    /**
     * Whether the task waits for a worker or runs now.
     */
    public function isPending(): bool
    {
        return $this === self::Queued || $this === self::Running;
    }
}
