<?php

declare(strict_types=1);

namespace Expansa\Scheduler;

use DateTime;
use Exception;
use ReflectionFunction;
use ReflectionException;
use InvalidArgumentException;

/**
 * Scheduler class.
 *
 * Fork of https://github.com/peppeocchi/php-cron-scheduler/tree/master.
 */
class Scheduler
{
    /**
     * Create new instance.
     *
     * @param  array  $config
     * @param  array  $jobs The queued jobs.
     * @param  array  $executedJobs Successfully executed jobs.
     * @param  array  $failedJobs Failed jobs.
     * @param  array  $outputSchedule The verbose output of the scheduled jobs.
     */
    public function __construct(
        private readonly array $config = [],
        private array $jobs = [],
        private array $executedJobs = [],
        private array $failedJobs = [],
        private array $outputSchedule = []
    ) {} // phpcs:ignore

    /**
     * Queue a job for execution in the correct queue.
     *
     * @param  Job  $job
     * @return void
     */
    private function queueJob(Job $job): void
    {
        $this->jobs[] = $job;
    }

    /**
     * Prioritise jobs in background.
     *
     * @return array
     */
    private function prioritiseJobs(): array
    {
        return array_merge(
            array_filter($this->jobs, fn($job) => $job->canRunInBackground()),
            array_filter($this->jobs, fn($job) => !$job->canRunInBackground())
        );
    }

    /**
     * Get the queued jobs.
     *
     * @return array
     */
    public function getQueuedJobs(): array
    {
        return $this->prioritiseJobs();
    }

    /**
     * Queues a function execution.
     *
     * @param callable    $fn   The function to execute
     * @param array       $args Optional arguments to pass to the php script
     * @param null|string $id   Optional custom identifier
     * @return Job
     */
    public function call(callable $fn, array $args = [], string $id = null): Job
    {
        $job = new Job($fn, $args, $id);

        $this->queueJob($job->configure($this->config));

        return $job;
    }

    /**
     * Queues a php script execution.
     *
     * @param string      $script The path to the php script to execute
     * @param null|string $bin    Optional path to the php binary
     * @param array       $args   Optional arguments to pass to the php script
     * @param null|string $id     Optional custom identifier
     * @return Job
     * @throws ReflectionException
     */
    public function php(string $script, ?string $bin = null, array $args = [], ?string $id = null): Job
    {
        $bin = is_string($bin) && file_exists($bin) ? $bin : (PHP_BINARY === '' ? '/usr/bin/php' : PHP_BINARY);

        $job = new Job($bin . ' ' . $script, $args, $id);

        if (! file_exists($script)) {
            $this->pushFailedJob($job, new InvalidArgumentException('The script should be a valid path to a file.'));
        }

        $this->queueJob($job->configure($this->config));

        return $job;
    }

    /**
     * Queue a raw shell command.
     *
     * @param string      $command The command to execute
     * @param array       $args    Optional arguments to pass to the command
     * @param null|string $id      Optional custom identifier
     * @return Job
     */
    public function raw(string $command, array $args = [], ?string $id = null): Job
    {
        $job = new Job($command, $args, $id);

        $this->queueJob($job->configure($this->config));

        return $job;
    }

    /**
     * Run the scheduler.
     *
     * @param null|DateTime $runTime Optional, run at specific moment
     * @return array  Executed jobs
     * @throws ReflectionException
     */
    public function run(?Datetime $runTime = null): array
    {
        $jobs = $this->getQueuedJobs();

        if (is_null($runTime)) {
            $runTime = new DateTime('now');
        }

        foreach ($jobs as $job) {
            if ($job->isDue($runTime)) {
                try {
                    $job->run();
                    $this->pushExecutedJob($job);
                } catch (Exception $e) {
                    $this->pushFailedJob($job, $e);
                }
            }
        }

        return $this->getExecutedJobs();
    }

    /**
     * Reset all collected data of last run.
     *
     * Call before run() if you call run() multiple times.
     */
    public function resetRun(): static
    {
        // Reset collected data of last run
        $this->executedJobs   = [];
        $this->failedJobs     = [];
        $this->outputSchedule = [];

        return $this;
    }

    /**
     * Add an entry to the scheduler verbose output array.
     *
     * @param string $string
     * @return void
     */
    private function addSchedulerVerboseOutput(string $string): void
    {
        $now = '[' . (new DateTime('now'))->format('c') . '] ';
        $this->outputSchedule[] = $now . $string;

        // Print to stdoutput in light gray
        // echo "\033[37m{$string}\033[0m\n";
    }

    /**
     * Push a successfully executed job.
     *
     * @param  Job  $job
     * @return void
     */
    private function pushExecutedJob(Job $job): void
    {
        $this->executedJobs[] = $job;

        $compiled = $job->compile();

        // If callable, log the string Closure
        if (is_callable($compiled)) {
            $compiled = 'Closure';
        }

        $this->addSchedulerVerboseOutput("Executing {$compiled}");
    }

    /**
     * Get the executed jobs.
     *
     * @return array
     */
    public function getExecutedJobs(): array
    {
        return $this->executedJobs;
    }

    /**
     * Push a failed job.
     *
     * @param Job       $job
     * @param Exception $e
     * @return void
     * @throws ReflectionException
     */
    private function pushFailedJob(Job $job, Exception $e): void
    {
        $this->failedJobs[] = new FailedJob($job, $e);

        $compiled = $job->compile();

        // If callable, log the string Closure
        if (is_callable($compiled)) {
            $reflectionClosure = new ReflectionFunction($compiled);

            $compiled = 'Closure ' . $reflectionClosure->getClosureScopeClass()->getName();
        }

        $this->addSchedulerVerboseOutput("{$e->getMessage()}: {$compiled}");
    }

    /**
     * Get the failed jobs.
     *
     * @return FailedJob[]
     */
    public function getFailedJobs(): array
    {
        return $this->failedJobs;
    }

    /**
     * Get the scheduler verbose output.
     *
     * @param string $type Allowed: text, html, array
     * @return string|array  The return depends on the requested $type
     */
    public function getVerboseOutput(string $type = 'text'): string|array
    {
        return match ($type) {
            'text'  => implode("\n", $this->outputSchedule),
            'html'  => implode('<br>', $this->outputSchedule),
            'array' => $this->outputSchedule,
            default => throw new InvalidArgumentException('Invalid output type'),
        };
    }

    /**
     * Remove all queued Jobs.
     */
    public function clearJobs(): static
    {
        $this->jobs = [];

        return $this;
    }

    /**
     * Start a worker.
     *
     * @param  array  $seconds - When the scheduler should run
     */
    public function work(array $seconds = [0])
    {
        while (true) {
            if (in_array((int) date('s'), $seconds, true)) {
                $this->run();
                sleep(1);
            }
        }
    }
}
