<?php

namespace Expansa\Cron;

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
