<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

use Expansa\Console\Command;
use Expansa\Facades\Hook;
use Expansa\Scheduler\Scheduler;

/**
 * Runs the scheduled jobs that are due now, meant to be called by the system cron every minute.
 * Jobs are added by the listeners of the `schedule` hook, which get the Scheduler.
 */
class ScheduleRun extends Command
{
    protected string $name = 'schedule:run';

    protected string $description = 'Run the scheduled jobs that are due now.';

    protected string $signature = 'schedule:run';

    public function handle(): void
    {
        $scheduler = new Scheduler(['tempDir' => EX_STORAGE]);

        Hook::call('schedule', $scheduler);

        $executed = $scheduler->run();
        $failed   = $scheduler->getFailedJobs();

        foreach ($executed as $job) {
            $this->info(t('[green]#Done# %s', $job->describe()));
        }

        foreach ($failed as $failure) {
            $this->error(t('Failed %s: %s', $failure->getJob()->describe(), $failure->getException()->getMessage()), null);
        }

        if ($executed === [] && $failed === []) {
            $this->info(t('No scheduled jobs are due.'));
        }

        // a non-zero exit code lets the system cron report the failure
        if ($failed !== []) {
            exit(1);
        }
    }

    public function getDescription(): string
    {
        return t('Run the scheduled jobs that are due now.');
    }
}
