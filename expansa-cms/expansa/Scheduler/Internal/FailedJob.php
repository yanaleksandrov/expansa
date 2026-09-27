<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Internal;

use Throwable;
use Expansa\Scheduler\Job;

/**
 * A job that threw during the run, or could not be queued; an item of Scheduler::$failedJobs.
 *
 * @internal
 * @package Expansa\Scheduler
 */
final readonly class FailedJob
{
    public function __construct(

        /**
         * Job that failed; may never have run if it could not be queued.
         */
        public Job $job,

        /**
         * Error thrown by the run, or ScriptNotFound when the job could not be queued.
         */
        public Throwable $exception,
    ) {}
}
