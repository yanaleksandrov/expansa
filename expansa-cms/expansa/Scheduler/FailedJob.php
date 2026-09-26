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

        /**
         * Job that failed; may never have run if it could not be scheduled.
         */
        private Job $job,

        /**
         * Error thrown by the run, or a SchedulerException when the job could not be scheduled.
         */
        private Throwable $exception,
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
