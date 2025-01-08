<?php

declare(strict_types=1);

namespace Expansa\Scheduler;

use DateTime;
use Exception;
use InvalidArgumentException;
use Cron\CronExpression;

class Job
{
    use JobIntervals;

    /**
     * A function to execute before the job is executed.
     *
     * @var callable
     */
    private $before;

    /**
     * A function to execute after the job is executed.
     *
     * @var callable
     */
    private $after;

    /**
     * A function to ignore an overlapping job. If true, the job will run also if it's overlapping.
     *
     * @var callable
     */
    private $whenOverlapping;

    /**
     * Create a new Job instance.
     *
     * @param callable|string     $command         Command to execute.
     * @param array               $args            Arguments to be passed to the command.
     * @param null|string         $id              Job identifier.
     * @param bool                $runInBackground Defines if the job should run in background.
     * @param null|DateTime       $creationTime    Creation time.
     * @param null|CronExpression $executionTime   Job schedule time.
     * @param null|string         $executionYear   Job schedule year.
     * @param string              $tempDir         Temporary directory path for lock files to prevent overlapping.
     * @param string              $lockFile        Path to the lock file.
     * @param bool                $truthTest       This could prevent the job to run. If true, the job will run.
     * @param mixed               $output          The output of the executed job.
     * @param int                 $returnCode      The return code of the executed job.
     * @param array               $outputTo        Files to write the output of the job.
     * @param array               $emailTo         Email addresses where the output should be sent to.
     * @param array               $emailConfig     Configuration for email sending.
     * @param null|string         $outputMode      Output mode for the executed job.
     */
    public function __construct(
        private readonly mixed $command,
        private readonly array $args = [],
        private ?string $id = null,
        private readonly ?CronExpression $executionTime = null,
        private readonly ?string $executionYear = null,
        private bool $runInBackground = true,
        private ?DateTime $creationTime = null,
        private string $tempDir = '',
        private string $lockFile = '',
        private bool $truthTest = true,
        private mixed $output = null,
        private int $returnCode = 0,
        private array $outputTo = [],
        private array $emailTo = [],
        private array $emailConfig = [],
        private ?string $outputMode = null
    )
    {
        if (!is_string($id)) {
            $this->id = match (true) {
                is_string($command) => md5($command),
                is_array($command)  => md5(serialize($command)),
                default             => spl_object_hash($command),
            };
        }

        $this->creationTime = new DateTime('now');

        // initialize the directory path for lock files
        $this->tempDir = sys_get_temp_dir();
    }

    /**
     * Get the Job id.
     *
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Check if the Job is due to run. It accepts as input a DateTime used to check if the job is due.
     * Defaults to job creation time. It also defaults the execution time if not previously defined.
     *
     * @param null|DateTime $date
     * @return bool
     */
    public function isDue(DateTime $date = null): bool
    {
        // The execution time is being defaulted if not defined
        if (! $this->executionTime) {
            $this->at('* * * * *');
        }

        $date = $date !== null ? $date : $this->creationTime;

        if ($this->executionYear && $this->executionYear !== $date->format('Y')) {
            return false;
        }

        return $this->executionTime->isDue($date);
    }

    /**
     * Check if the Job is overlapping.
     *
     * @return bool
     */
    public function isOverlapping(): bool
    {
        return $this->lockFile &&
               file_exists($this->lockFile) &&
               call_user_func($this->whenOverlapping, filemtime($this->lockFile)) === false;
    }

    /**
     * Force the Job to run in foreground.
     *
     * @return self
     */
    public function inForeground(): static
    {
        $this->runInBackground = false;

        return $this;
    }

    /**
     * Check if the Job can run in background.
     *
     * @return bool
     */
    public function canRunInBackground(): bool
    {
        return !(is_callable($this->command) || $this->runInBackground === false);
    }

    /**
     * This will prevent the Job from overlapping. It prevents another instance of the
     * same Job of being executed if the previous is still running. The job id is used
     * as a filename for the lock file.
     *
     * @param string        $tempDir         The directory path for the lock files
     * @param null|callable $whenOverlapping A callback to ignore job overlapping
     * @return self
     */
    public function onlyOne(string $tempDir = '', callable $whenOverlapping = null): static
    {
        if (!$tempDir || ! is_dir($tempDir)) {
            $tempDir = $this->tempDir;
        }

        $this->lockFile = implode('/', [trim($tempDir), trim($this->id) . '.lock']);
        if ($whenOverlapping) {
            $this->whenOverlapping = $whenOverlapping;
        } else {
            $this->whenOverlapping = fn () => false;
        }

        return $this;
    }

    /**
     * Compile the Job command.
     *
     * @return string|callable
     */
    public function compile(): string|callable
    {
        $compiled = $this->command;

        // If callable, return the function itself
        if (is_callable($compiled)) {
            return $compiled;
        }

        // Augment with any supplied arguments
        foreach ($this->args as $key => $value) {
            $compiled .= ' ' . escapeshellarg($key);
            if ($value !== null) {
                $compiled .= ' ' . escapeshellarg($value);
            }
        }

        // Add the boilerplate to redirect the output to file/s
        if (count($this->outputTo) > 0) {
            $compiled .= ' | tee ';
            $compiled .= $this->outputMode === 'a' ? '-a ' : '';
            foreach ($this->outputTo as $file) {
                $compiled .= $file . ' ';
            }

            $compiled = trim($compiled);
        }

        // Add boilerplate to remove lockfile after execution
        if ($this->lockFile) {
            $compiled .= '; rm ' . $this->lockFile;
        }

        // Add boilerplate to run in background
        if ($this->canRunInBackground()) {
            // Parentheses are need execute the chain of commands in a subshell
            // that can then run in background
            $compiled = '(' . $compiled . ') > /dev/null 2>&1 &';
        }

        return trim($compiled);
    }

    /**
     * Configure the job.
     *
     * @param  array  $config
     * @return self
     */
    public function configure(array $config = []): static
    {
        if (isset($config['email'])) {
            if (! is_array($config['email'])) {
                throw new InvalidArgumentException('Email configuration should be an array.');
            }
            $this->emailConfig = $config['email'];
        }

        // Check if config has defined a tempDir
        if (is_dir($config['tempDir'] ?? null)) {
            $this->tempDir = $config['tempDir'];
        }

        return $this;
    }

    /**
     * Truth test to define if the job should run if due.
     *
     * @param  callable  $fn
     * @return self
     */
    public function when(callable $fn): static
    {
        $this->truthTest = $fn();

        return $this;
    }

    /**
     * Run the job.
     *
     * @return bool
     * @throws Exception
     */
    public function run(): bool
    {
        // If the truthTest failed, don't run
        if ($this->truthTest !== true) {
            return false;
        }

        // If overlapping, don't run
        if ($this->isOverlapping()) {
            return false;
        }

        $compiled = $this->compile();

        // Write lock file if necessary
        $this->createLockFile();

        if (is_callable($this->before)) {
            call_user_func($this->before);
        }

        if (is_callable($compiled)) {
            $this->output = $this->exec($compiled);
        } else {
            exec($compiled, $this->output, $this->returnCode);
        }

        $this->finalise();

        return true;
    }

    /**
     * Create the job lock file.
     *
     * @param null|mixed $content
     * @return void
     */
    private function createLockFile(mixed $content = null): void
    {
        if ($this->lockFile) {
            if (! is_string($content)) {
                $content = $this->getId();
            }

            file_put_contents($this->lockFile, $content);
        }
    }

    /**
     * Remove the job lock file.
     *
     * @return void
     */
    private function removeLockFile(): void
    {
        if ($this->lockFile && file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }

    /**
     * Execute a callable job.
     *
     * @param  callable  $fn
     * @throws Exception
     * @return string
     */
    private function exec(callable $fn): string
    {
        ob_start();

        try {
            $returnData = call_user_func_array($fn, $this->args);
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }

        $outputBuffer = ob_get_clean();

        foreach ($this->outputTo as $filename) {
            if ($outputBuffer) {
                file_put_contents($filename, $outputBuffer, $this->outputMode === 'a' ? FILE_APPEND : 0);
            }

            if ($returnData) {
                file_put_contents($filename, $returnData, FILE_APPEND);
            }
        }

        $this->removeLockFile();

        return $outputBuffer . (is_string($returnData) ? $returnData : '');
    }

    /**
     * Set the file/s where to write the output of the job.
     *
     * @param array|string $filename
     * @param bool         $append
     * @return self
     */
    public function output(array|string $filename, bool $append = false): static
    {
        $this->outputTo   = is_array($filename) ? $filename : [$filename];
        $this->outputMode = $append === false ? 'w' : 'a';

        return $this;
    }

    /**
     * Get the job output.
     *
     * @return mixed
     */
    public function getOutput(): mixed
    {
        return $this->output;
    }

    /**
     * Set the emails where the output should be sent to.
     * The Job should be set to write output to a file for this to work.
     *
     * @param array|string $email
     * @return self
     */
    public function email(array|string $email): static
    {
        if (! is_string($email) && ! is_array($email)) {
            throw new InvalidArgumentException('The email can be only string or array');
        }

        $this->emailTo = is_array($email) ? $email : [$email];

        // Force the job to run in foreground
        $this->inForeground();

        return $this;
    }

    /**
     * Finalise the job after execution.
     *
     * @return void
     */
    private function finalise(): void
    {
        // Send output to email
        $this->emailOutput();

        // Call any callback defined
        if (is_callable($this->after)) {
            call_user_func($this->after, $this->output, $this->returnCode);
        }
    }

    /**
     * Email the output of the job, if any.
     *
     * @return bool
     */
    private function emailOutput(): bool
    {
        if (! count($this->outputTo) || ! count($this->emailTo)) {
            return false;
        }

        if (
            isset($this->emailConfig['ignore_empty_output']) &&
            $this->emailConfig['ignore_empty_output'] === true &&
            empty($this->output)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Set function to be called before job execution
     * Job object is injected as a parameter to callable function.
     *
     * @param callable $fn
     * @return self
     */
    public function before(callable $fn): static
    {
        $this->before = $fn;

        return $this;
    }

    /**
     * Set a function to be called after job execution. By default, this will force
     * the job to run in foreground because the output is injected as a parameter of this
     * function, but it could be avoided by passing true as a second parameter. The job
     * will run in background if it meets all the other criteria.
     *
     * @param  callable $fn
     * @param bool      $runInBackground
     * @return self
     */
    public function then(callable $fn, bool $runInBackground = false): static
    {
        $this->after = $fn;

        // Force the job to run in foreground
        if ($runInBackground === false) {
            $this->inForeground();
        }

        return $this;
    }

    /**
     * Get the execution time for the job.
     *
     * If no execution time is set, a default CronExpression
     * for every minute (* * * * *) is returned.
     *
     * @return CronExpression The cron expression representing the execution time.
     */
    public function getExecutionTime(): CronExpression
    {
        if (! $this->executionTime) {
            return new CronExpression('* * * * *');
        }

        return $this->executionTime;
    }
}
