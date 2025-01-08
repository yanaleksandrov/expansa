<?php

declare(strict_types=1);

namespace Expansa\Scheduler;

use Exception;

readonly class FailedJob
{
    public function __construct(
        private Job $job,
        private Exception $exception
    ) {} // phpcs:ignore

    public function getJob(): Job
    {
        return $this->job;
    }

    public function getException(): Exception
    {
        return $this->exception;
    }
}
