<?php

declare(strict_types=1);

namespace Expansa\Scheduler;

use Closure;
use DateTime;
use DateTimeInterface;
use Throwable;
use Expansa\Scheduler\Exception\SchedulerException;

/**
 * Queues closures, PHP scripts and shell commands and runs the due ones.
 * Meant to be called once a minute by the system cron, or kept alive by work().
 * Fork of https://github.com/peppeocchi/php-cron-scheduler.
 *
 * @package Expansa\Scheduler
 */
class Scheduler
{
    /**
     * Queued jobs.
     *
     * @var Job[]
     */
    private array $jobs = [];

    /**
     * Jobs executed by the last run.
     *
     * @var Job[]
     */
    private array $executedJobs = [];

    /**
     * Jobs failed during the last run or while being queued.
     *
     * @var FailedJob[]
     */
    private array $failedJobs = [];

    /**
     * Log lines of the last run.
     *
     * @var string[]
     */
    private array $outputSchedule = [];

    public function __construct(

        /**
         * `tempDir` for lock files and `email` settings, applied to every queued job.
         */
        private readonly array $config = [],
    ) {} // phpcs:ignore

    /**
     * Queue a PHP callable.
     *
     * @param callable    $fn
     * @param array       $args Arguments passed to the callable, string keys are named arguments.
     * @param string|null $id   Custom identifier.
     * @return Job
     */
    public function call(callable $fn, array $args = [], ?string $id = null): Job
    {
        return $this->queue(new Job($fn instanceof Closure ? $fn : $fn(...), $args, $id));
    }

    /**
     * Queue a PHP script, run by a separate PHP process.
     * A missing script is reported as a failed job and is not queued.
     *
     * @param string      $script Path to the script.
     * @param string|null $bin    Path to the PHP binary, the current one by default.
     * @param array       $args   Script arguments.
     * @param string|null $id     Custom identifier.
     * @return Job
     */
    public function php(string $script, ?string $bin = null, array $args = [], ?string $id = null): Job
    {
        $bin = $bin !== null && is_file($bin) ? $bin : (PHP_BINARY ?: '/usr/bin/php');
        $job = new Job(escapeshellarg($bin) . ' ' . escapeshellarg($script), $args, $id);

        if (! is_file($script)) {
            $this->pushFailedJob($job->configure($this->config), new SchedulerException("The script $script does not exist."));

            return $job;
        }

        return $this->queue($job);
    }

    /**
     * Queue a raw shell command.
     *
     * @param string      $command
     * @param array       $args Arguments, escaped: `['--force' => null, '--env' => 'dev', 'file.txt']`.
     * @param string|null $id   Custom identifier.
     * @return Job
     */
    public function raw(string $command, array $args = [], ?string $id = null): Job
    {
        return $this->queue(new Job($command, $args, $id));
    }

    /**
     * Get the queued jobs, background ones first.
     *
     * @return Job[]
     */
    public function getQueuedJobs(): array
    {
        return $this->prioritise($this->jobs);
    }

    /**
     * Run the due jobs, background ones first so that foreground jobs do not delay them.
     *
     * @param DateTimeInterface|null $runTime The moment to check the jobs against, `now` by default.
     * @return Job[] Jobs executed by this run.
     */
    public function run(?DateTimeInterface $runTime = null): array
    {
        $runTime ??= new DateTime();

        $due = [];
        foreach ($this->jobs as $job) {
            if ($job->isDue($runTime)) {
                $due[] = $job;
            }
        }

        foreach ($this->prioritise($due) as $job) {
            try {
                if ($job->run()) {
                    $this->pushExecutedJob($job);
                }
            } catch (Throwable $e) {
                $this->pushFailedJob($job, $e);
            }
        }

        return $this->executedJobs;
    }

    /**
     * Reset the results of the last run, call it before run() when it is called several times.
     *
     * @return static
     */
    public function resetRun(): static
    {
        $this->executedJobs   = [];
        $this->failedJobs     = [];
        $this->outputSchedule = [];

        return $this;
    }

    /**
     * Get the jobs executed by the last run.
     *
     * @return Job[]
     */
    public function getExecutedJobs(): array
    {
        return $this->executedJobs;
    }

    /**
     * Get the jobs failed during the last run or while being queued.
     *
     * @return FailedJob[]
     */
    public function getFailedJobs(): array
    {
        return $this->failedJobs;
    }

    /**
     * Get the log of the last run.
     *
     * @param string $type `text`, `html` or `array`.
     * @return string|string[]
     * @throws SchedulerException
     */
    public function getVerboseOutput(string $type = 'text'): string|array
    {
        return match ($type) {
            'text'  => implode("\n", $this->outputSchedule),
            'html'  => implode('<br>', array_map('htmlspecialchars', $this->outputSchedule)),
            'array' => $this->outputSchedule,
            default => throw new SchedulerException('Invalid output type'),
        };
    }

    /**
     * Remove all queued jobs.
     *
     * @return static
     */
    public function clearJobs(): static
    {
        $this->jobs = [];

        return $this;
    }

    /**
     * Run the scheduler forever, at the given seconds of every minute.
     * Sleeps until the next of these seconds, each run starts with fresh results.
     *
     * @param int[] $seconds From 0 to 59.
     * @return never
     * @throws SchedulerException
     */
    public function work(array $seconds = [0]): never
    {
        $seconds = array_values(array_unique(array_map('intval', $seconds)));
        sort($seconds);

        if ($seconds === [] || $seconds[0] < 0 || $seconds[count($seconds) - 1] > 59) {
            throw new SchedulerException('The seconds should be between 0 and 59.');
        }

        while (true) {
            $now    = microtime(true);
            $second = (int) date('s', (int) $now);
            $next   = $seconds[0] + 60;

            foreach ($seconds as $candidate) {
                if ($candidate > $second) {
                    $next = $candidate;
                    break;
                }
            }

            usleep((int) (($next - $second - fmod($now, 1)) * 1_000_000));

            $this->resetRun()->run();
        }
    }

    /**
     * Configure and queue a job.
     *
     * @param Job $job
     * @return Job
     */
    private function queue(Job $job): Job
    {
        $this->jobs[] = $job->configure($this->config);

        return $job;
    }

    /**
     * Order jobs so that background ones come first, keeping the queue order otherwise.
     *
     * @param Job[] $jobs
     * @return Job[]
     */
    private function prioritise(array $jobs): array
    {
        $background = [];
        $foreground = [];

        foreach ($jobs as $job) {
            if ($job->canRunInBackground()) {
                $background[] = $job;
            } else {
                $foreground[] = $job;
            }
        }

        return [...$background, ...$foreground];
    }

    private function log(string $message): void
    {
        $this->outputSchedule[] = '[' . date('c') . '] ' . $message;
    }

    private function pushExecutedJob(Job $job): void
    {
        $this->executedJobs[] = $job;

        $this->log('Executed ' . $job->describe());
    }

    private function pushFailedJob(Job $job, Throwable $e): void
    {
        $this->failedJobs[] = new FailedJob($job, $e);

        $this->log($e->getMessage() . ': ' . $job->describe());
    }
}
