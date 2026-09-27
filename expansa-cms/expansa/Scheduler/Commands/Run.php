<?php

declare(strict_types=1);

namespace Expansa\Scheduler\Commands;

use Closure;
use Expansa\Console\Commands\AbstractCommand;
use Expansa\Scheduler\Scheduler;

/**
 * The `schedule:run` command: runs the jobs that are due now, meant to be called by the system cron every minute.
 * Exits with code 1 if a job failed, so the cron can report it.
 *
 * @package Expansa\Scheduler
 */
final class Run extends AbstractCommand
{
    public string $name = 'schedule:run';

    public string $signature = 'schedule:run';

    public function __construct(

        /**
         * Queues the jobs, gets the Scheduler; bootstrap.php passes the `schedule` hook.
         */
        private readonly Closure $schedule,
    ) {}

    public function getDescription(): string
    {
        return t('Run the scheduled jobs that are due now.');
    }

    public function handle(): void
    {
        $scheduler = new Scheduler();

        ($this->schedule)($scheduler);

        $executed = $scheduler->run();
        $failed   = $scheduler->failedJobs;

        foreach ($executed as $job) {
            $this->info(t('[green]#Done# %s', $job->describe()));
        }

        foreach ($failed as $failure) {
            $this->error(t('Failed %s: %s', $failure->job->describe(), $failure->exception->getMessage()), null);
        }

        if ($executed === [] && $failed === []) {
            $this->info(t('No scheduled jobs are due.'));
        }

        if ($failed !== []) {
            exit(1);
        }
    }
}
