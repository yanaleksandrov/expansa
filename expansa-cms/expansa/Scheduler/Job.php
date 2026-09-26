<?php

declare(strict_types=1);

namespace Expansa\Scheduler;

use Closure;
use DateTime;
use DateTimeInterface;
use ReflectionFunction;
use Stringable;
use Throwable;
use Expansa\Scheduler\Exceptions\EmailNotSent;
use Expansa\Scheduler\Exceptions\InvalidConfiguration;
use Expansa\Scheduler\Traits\HasIntervals;

/**
 * A scheduled PHP closure or shell command.
 * Shell commands run in background through a POSIX shell unless forced to foreground, on Windows always in foreground.
 *
 * @package Expansa\Scheduler
 */
final class Job
{
    use HasIntervals;

    private const bool POSIX = PHP_OS_FAMILY !== 'Windows';

    /**
     * Schedule of the job, `null` runs it every minute.
     */
    private ?CronExpression $executionTime = null;

    /**
     * The only year the job runs in, set by date().
     */
    private ?string $executionYear = null;

    private bool $runInBackground = true;

    /**
     * Lock files directory from the scheduler config, empty for the system temp directory.
     */
    private string $tempDir = '';

    private string $lockFile = '';

    /**
     * Decides if an overlapping job still runs, gets the lock file mtime.
     */
    private ?Closure $whenOverlapping = null;

    /**
     * Truth test checked right before the run, the job runs only on `true`.
     */
    private Closure|bool $truthTest = true;

    private ?Closure $before = null;

    private ?Closure $after = null;

    /**
     * Output of the last run, `null` before the first run or for a background job.
     */
    public private(set) ?string $output = null;

    /**
     * Exit code of the last foreground shell run.
     */
    public private(set) int $returnCode = 0;

    /**
     * Files the output is written to.
     *
     * @var string[]
     */
    private array $outputTo = [];

    private bool $appendOutput = false;

    /**
     * Emails the output is sent to.
     *
     * @var string[]
     */
    private array $emailTo = [];

    /**
     * Email settings: `subject`, `body` and `ignore_empty_output`.
     */
    private array $email = [];

    /**
     * Sends an email: gets the address, subject, body and attached files, returns true when sent.
     */
    private ?Closure $mailer = null;

    public function __construct(

        /**
         * A closure, or a shell command.
         */
        private readonly Closure|string $command,

        /**
         * Closure arguments (string keys are named ones), or shell arguments:
         * `['--force' => null, '--env' => 'dev', 'file.txt']`.
         */
        private readonly array $args = [],

        /**
         * Identifier used as the lock file name, derived from the command by default.
         */
        private ?string $id = null,
    ) {} // phpcs:ignore

    /**
     * Get the job identifier: md5 of the shell command, or of the closure location.
     *
     * @return string
     */
    public function getId(): string
    {
        if ($this->id !== null) {
            return $this->id;
        }

        if (is_string($this->command)) {
            return $this->id = md5($this->compileCommand());
        }

        $reflection = new ReflectionFunction($this->command);
        $location   = $reflection->getFileName() . ':' . $reflection->getStartLine();

        return $this->id = md5($reflection->getClosureScopeClass()?->name . '::' . $reflection->name . '@' . $location);
    }

    /**
     * Apply the scheduler configuration, see Scheduler::configure().
     *
     * @param string       $tempDir Lock files directory, the system temp one when empty.
     * @param array        $email   `subject`, `body` and `ignore_empty_output`.
     * @param Closure|null $mailer  Gets the address, subject, body and attached files, returns true when sent.
     * @return static
     */
    public function configure(string $tempDir = '', array $email = [], ?Closure $mailer = null): static
    {
        $this->tempDir = $tempDir;
        $this->email   = $email;
        $this->mailer  = $mailer;

        return $this;
    }

    /**
     * Check if the job is due at a date, `now` by default.
     *
     * @param DateTimeInterface|null $date
     * @return bool
     */
    public function isDue(?DateTimeInterface $date = null): bool
    {
        $date ??= new DateTime();

        if ($this->executionYear !== null && $this->executionYear !== $date->format('Y')) {
            return false;
        }

        return $this->executionTime === null || $this->executionTime->isDue($date);
    }

    /**
     * Get the schedule of the job.
     *
     * @return CronExpression
     */
    public function getExecutionTime(): CronExpression
    {
        return $this->executionTime ??= new CronExpression('* * * * *');
    }

    /**
     * Run the job only if a condition is true at the moment of the run.
     *
     * @param callable|bool $condition Callable is called only when the job is due.
     * @return static
     */
    public function when(callable|bool $condition): static
    {
        $this->truthTest = is_bool($condition) ? $condition : $condition(...);

        return $this;
    }

    /**
     * Prevent the job from running while its previous run is still in progress.
     *
     * @param string        $tempDir         Lock files directory, the configured or the system temp one by default.
     * @param callable|null $whenOverlapping Gets the lock file mtime, returns `true` to run the job anyway.
     * @return static
     */
    public function onlyOne(string $tempDir = '', ?callable $whenOverlapping = null): static
    {
        $dir = match (true) {
            $tempDir !== '' && is_dir($tempDir)             => $tempDir,
            $this->tempDir !== '' && is_dir($this->tempDir) => $this->tempDir,
            default                                         => sys_get_temp_dir(),
        };

        $this->lockFile        = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $this->getId() . '.lock';
        $this->whenOverlapping = $whenOverlapping === null ? null : $whenOverlapping(...);

        return $this;
    }

    /**
     * Check if the previous run of the job still holds its lock.
     *
     * @return bool
     */
    public function isOverlapping(): bool
    {
        if ($this->lockFile === '' || ! is_file($this->lockFile)) {
            return false;
        }

        return $this->whenOverlapping === null || ($this->whenOverlapping)(filemtime($this->lockFile)) !== true;
    }

    /**
     * Force the job to run in foreground.
     *
     * @return static
     */
    public function inForeground(): static
    {
        $this->runInBackground = false;

        return $this;
    }

    /**
     * Check if the job runs in background: a shell command on a POSIX system, not forced to foreground.
     *
     * @return bool
     */
    public function canRunInBackground(): bool
    {
        return self::POSIX && $this->runInBackground && is_string($this->command);
    }

    /**
     * Write the output to files, a callable job output is its echo plus the returned string.
     *
     * @param array|string $filename
     * @param bool         $append
     * @return static
     */
    public function output(array|string $filename, bool $append = false): static
    {
        $this->outputTo     = (array) $filename;
        $this->appendOutput = $append;

        return $this;
    }

    /**
     * Send the output by email after each run, the output files are attached. Forces the job to foreground.
     *
     * @param array|string $email
     * @return static
     */
    public function email(array|string $email): static
    {
        $this->emailTo = (array) $email;

        return $this->inForeground();
    }

    /**
     * Call a function before the run, it gets the job.
     *
     * @param callable $fn
     * @return static
     */
    public function before(callable $fn): static
    {
        $this->before = $fn(...);

        return $this;
    }

    /**
     * Call a function after the run, it gets the output and the exit code.
     * Forces the job to foreground: a background job has no output.
     *
     * @param callable $fn
     * @param bool     $runInBackground Keep the job in background anyway.
     * @return static
     */
    public function then(callable $fn, bool $runInBackground = false): static
    {
        $this->after = $fn(...);

        return $runInBackground ? $this : $this->inForeground();
    }

    /**
     * Compile the job: the closure itself, or the shell command with arguments and, in background, its output and lock handling.
     *
     * @return Closure|string
     */
    public function compile(): Closure|string
    {
        if ($this->command instanceof Closure) {
            return $this->command;
        }

        $compiled = $this->compileCommand();
        if (! $this->canRunInBackground()) {
            return $compiled;
        }

        if ($this->outputTo !== []) {
            $files     = implode(' ', array_map('escapeshellarg', $this->outputTo));
            $compiled .= ' | tee ' . ($this->appendOutput ? '-a ' : '') . $files;
        }

        if ($this->lockFile !== '') {
            $compiled .= '; rm -f ' . escapeshellarg($this->lockFile);
        }

        // a subshell runs the whole chain in background
        return '(' . $compiled . ') > /dev/null 2>&1 &';
    }

    /**
     * Describe the job for logs: the shell command or the closure location.
     *
     * @return string
     */
    public function describe(): string
    {
        if (is_string($this->command)) {
            return $this->compileCommand();
        }

        $reflection = new ReflectionFunction($this->command);
        $file       = $reflection->getFileName();

        return 'Closure ' . ($file === false ? $reflection->name : $file . ':' . $reflection->getStartLine());
    }

    /**
     * Run the job if the truth test passes and it does not overlap.
     *
     * @return bool False if the job was skipped.
     * @throws Throwable Whatever the job, its callbacks or the mailer throw.
     */
    public function run(): bool
    {
        if (! $this->passesTruthTest() || $this->isOverlapping()) {
            return false;
        }

        if ($this->lockFile !== '') {
            file_put_contents($this->lockFile, $this->getId());
        }

        try {
            if ($this->before !== null) {
                ($this->before)($this);
            }

            $this->output = $this->runCompiled();
        } catch (Throwable $e) {
            $this->deleteLockFile();

            throw $e;
        }

        // a background job removes the lock itself when it finishes
        if (! $this->canRunInBackground()) {
            $this->deleteLockFile();
        }

        $this->emailOutput();

        if ($this->after !== null) {
            ($this->after)($this->output, $this->returnCode);
        }

        return true;
    }

    /**
     * Execute the compiled job and collect its output.
     *
     * @return string|null `null` for a background job.
     */
    private function runCompiled(): ?string
    {
        $compiled = $this->compile();

        if (is_string($compiled)) {
            $lines = [];
            exec($compiled, $lines, $this->returnCode);

            if ($this->canRunInBackground()) {
                return null;
            }

            $output = implode("\n", $lines);
            $this->writeOutput($lines === [] ? '' : $output . "\n");

            return $output;
        }

        ob_start();
        try {
            $result = $compiled(...$this->args);
        } finally {
            $output = (string) ob_get_clean();
        }

        if (is_string($result) || $result instanceof Stringable) {
            $output .= $result;
        }

        $this->writeOutput($output);

        return $output;
    }

    /**
     * Get the shell command with its escaped arguments.
     *
     * @return string
     */
    private function compileCommand(): string
    {
        $compiled = $this->command;

        foreach ($this->args as $key => $value) {
            if (is_string($key)) {
                $compiled .= ' ' . escapeshellarg($key);
            }
            if ($value !== null) {
                $compiled .= ' ' . escapeshellarg((string) $value);
            }
        }

        return $compiled;
    }

    private function passesTruthTest(): bool
    {
        return $this->truthTest instanceof Closure ? ($this->truthTest)() === true : $this->truthTest;
    }

    private function writeOutput(string $output): void
    {
        foreach ($this->outputTo as $file) {
            file_put_contents($file, $output, $this->appendOutput ? FILE_APPEND : 0);
        }
    }

    private function deleteLockFile(): void
    {
        if ($this->lockFile !== '' && is_file($this->lockFile)) {
            unlink($this->lockFile);
        }
    }

    /**
     * Email the output to each recipient set by email(), with the existing output files attached.
     *
     * @return void
     * @throws InvalidConfiguration|EmailNotSent
     */
    private function emailOutput(): void
    {
        if ($this->emailTo === []) {
            return;
        }

        if (($this->email['ignore_empty_output'] ?? false) === true && ($this->output ?? '') === '') {
            return;
        }

        if ($this->mailer === null) {
            throw new InvalidConfiguration('The job output is not sent: no mailer is configured.');
        }

        $subject     = $this->email['subject'] ?? 'Cronjob execution';
        $body        = $this->email['body'] ?? (string) $this->output;
        $attachments = array_values(array_filter($this->outputTo, is_file(...)));

        foreach ($this->emailTo as $to) {
            if (($this->mailer)($to, $subject, $body, $attachments) !== true) {
                throw new EmailNotSent("The job output is not sent to $to.");
            }
        }
    }
}
