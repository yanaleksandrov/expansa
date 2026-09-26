<?php

declare(strict_types=1);

namespace Expansa\Scheduler;

use Throwable;

/**
 * A job that threw during the run, or could not be scheduled.
 *
 * @package Expansa\Scheduler
 */
readonly class FailedJob
{
    public function __construct(
        private Job $job,
        private Throwable $exception
    ) {} // phpcs:ignore

    public function getJob(): Job
    {
        return $this->job;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }
}
