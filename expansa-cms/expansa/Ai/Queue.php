<?php

declare(strict_types=1);

namespace Expansa\Ai;

use Closure;
use Exception;
use Expansa\Ai\Contracts\Store;
use Expansa\Ai\Enums\Status;
use Expansa\Ai\Exceptions\BudgetExceeded;
use Expansa\Ai\Exceptions\ClarificationUnavailable;
use Expansa\Ai\Exceptions\EmptyRequest;
use Expansa\Ai\Exceptions\InputTooLong;
use Throwable;

/**
 * Runs generation in the background: a request returns a task at once, a worker process generates the draft.
 * The web request calls `dispatch()` or `clarify()`, the `ai:work` command calls `work()`,
 * and the interface polls `get()` until the task leaves the pending states.
 */
final class Queue
{
    /**
     * Failures that repeat on every attempt, so the task fails at once.
     */
    private const array PERMANENT = [
        EmptyRequest::class, InputTooLong::class, BudgetExceeded::class, ClarificationUnavailable::class,
    ];

    /**
     * Stores the task storage, the manager factory, and the worker launcher.
     */
    public function __construct(

        /**
         * Task storage shared by web requests and workers.
         */
        public readonly Store $store,

        /**
         * Creates the manager; called only in the worker, so web requests do not build the provider.
         *
         * @var Closure(): Manager
         */
        public readonly Closure $manager,

        /**
         * Starts a background worker for a task id; null leaves the task to the scheduled `ai:work`.
         *
         * @var (Closure(string): void)|null
         */
        public readonly ?Closure $start = null,

        /**
         * Seconds after which a running task counts as dead: the worker crashed or hit a time limit.
         */
        public readonly int $timeout = 900,

        /**
         * Worker runs per round before a task with temporary failures fails.
         */
        public readonly int $attempts = 3,
    ) {}

    /**
     * Queues a new request and starts a worker for it.
     *
     * @param string $input User's natural language request
     * @param string $owner Who created the task, e.g. a user id
     * @return Task Queued task
     * @throws EmptyRequest When the request is empty
     */
    public function dispatch(string $input, string $owner = ''): Task
    {
        if (trim($input) === '') {
            throw new EmptyRequest('The plugin request cannot be empty.');
        }

        $task = new Task(bin2hex(random_bytes(16)), $input, $owner, createdAt: time(), updatedAt: time());
        $this->store->set($task);
        $this->launch($task);

        return $task;
    }

    /**
     * Queues an answer to the task's questions and starts a worker for it.
     *
     * @param string $id Task id
     * @param string $answer User's response to the pending questions
     * @return Task Queued task
     * @throws EmptyRequest When the answer is empty
     * @throws ClarificationUnavailable When the task does not exist or asks no questions
     */
    public function clarify(string $id, string $answer): Task
    {
        if (trim($answer) === '') {
            throw new EmptyRequest('The clarification answer cannot be empty.');
        }

        $task = $this->store->get($id);
        if ($task?->status !== Status::Questions) {
            throw new ClarificationUnavailable('The task does not wait for an answer.');
        }

        $task->status = Status::Queued;
        $task->answer = trim($answer);
        $task->error = '';
        $task->attempts = 0;
        $task->updatedAt = time();
        $this->store->set($task);
        $this->launch($task);

        return $task;
    }

    /**
     * Returns a task by id.
     *
     * @param string $id Task id
     * @return Task|null Task, or null when it does not exist
     */
    public function get(string $id): ?Task
    {
        return $this->store->get($id);
    }

    /**
     * Claims one task and runs a generation round for it; for the worker process.
     * Temporary failures queue the task again until `attempts` runs are spent.
     *
     * @param string|null $id Run only this task
     * @return Task|null Processed task, or null when no task is available
     */
    public function work(?string $id = null): ?Task
    {
        $task = $this->store->claim($this->timeout, $id);
        if ($task === null) {
            return null;
        }

        if ($task->attempts > $this->attempts) {
            return $this->finish($task, Status::Failed, $task->error ?: 'The worker stopped before finishing the task.');
        }

        try {
            $manager = ($this->manager)();
            $task->draft = $task->answer === null || $task->draft === null
                ? $manager->create($task->input)
                : $manager->clarify($task->draft->session, $task->answer);
        } catch (Throwable $error) {
            // an Error is a bug and repeats; an Exception may be a temporary provider or network failure
            $isPermanent = ! $error instanceof Exception
                || in_array($error::class, self::PERMANENT, true)
                || $task->attempts >= $this->attempts;

            return $this->finish($task, $isPermanent ? Status::Failed : Status::Queued, $error->getMessage());
        }

        $task->answer = null;

        return $this->finish($task, match (true) {
            $task->draft->missingExtensions !== [] => Status::MissingExtensions,
            $task->draft->questions !== []         => Status::Questions,
            $task->draft->valid                    => Status::Ready,
            default                                => Status::Invalid,
        });
    }

    /**
     * Saves the result of a worker run.
     *
     * @param Task $task Claimed task
     * @param Status $status New state
     * @param string $error Failure message, empty on success
     */
    private function finish(Task $task, Status $status, string $error = ''): Task
    {
        $task->status = $status;
        $task->error = $error;
        $task->attempts = $status === Status::Queued ? $task->attempts : 0;
        $task->updatedAt = time();
        $this->store->set($task);

        return $task;
    }

    /**
     * Starts a worker; when it can not start, the task stays queued for the scheduled `ai:work`
     * and keeps the reason in `error`.
     *
     * @param Task $task Queued task
     */
    private function launch(Task $task): void
    {
        if ($this->start === null) {
            return;
        }

        try {
            ($this->start)($task->id);
        } catch (Throwable $error) {
            $task->error = "The worker did not start: {$error->getMessage()}";
            $this->store->set($task);
        }
    }
}
