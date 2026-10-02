<?php

declare(strict_types=1);

namespace Expansa\Ai;

use Closure;
use Exception;
use Expansa\Ai\Contracts\Store;
use Expansa\Ai\Enums\Status;
use Expansa\Ai\Exceptions\BudgetExceeded;
use Expansa\Ai\Exceptions\Cancelled;
use Expansa\Ai\Exceptions\ClarificationUnavailable;
use Expansa\Ai\Exceptions\EmptyRequest;
use Expansa\Ai\Exceptions\InputTooLong;
use Throwable;

/**
 * Runs generation in the background: a request returns a task at once, a worker process generates the draft.
 * The web request calls `dispatch()` or `clarify()`, the `ai:work` command calls `work()`,
 * and the interface polls `get()` until the task leaves the pending states.
 * The worker saves the task after each step, so a poll shows the steps finished so far and the running one.
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
     * Cancels a task that waits for a worker, runs, or waits for an answer.
     * A running worker stops at the start or end of its current step, not inside a provider call.
     *
     * @param string $id Task id
     * @return bool Whether the task was cancelled; false when it does not exist or has finished
     */
    public function cancel(string $id): bool
    {
        $task = $this->store->get($id);
        if ($task === null || ! $task->status->isPending() && $task->status !== Status::Questions) {
            return false;
        }

        $task->status = Status::Cancelled;
        $task->answer = null;
        $task->updatedAt = time();
        $this->store->set($task);

        return true;
    }

    /**
     * Renames a task; an empty title shows the request again.
     *
     * @param string $id Task id
     * @param string $title New name
     * @return bool Whether the task exists
     */
    public function rename(string $id, string $title): bool
    {
        return $this->change($id, function (Task $task) use ($title): void {
            $task->title = trim($title);
        });
    }

    /**
     * Moves a task out of the list or back.
     *
     * @param string $id Task id
     * @param bool $isArchived Whether the task is archived
     * @return bool Whether the task exists
     */
    public function archive(string $id, bool $isArchived = true): bool
    {
        return $this->change($id, function (Task $task) use ($isArchived): void {
            $task->isArchived = $isArchived;
        });
    }

    /**
     * Deletes a task; a running worker stops at its next step.
     *
     * @param string $id Task id
     * @return bool Whether the task existed
     */
    public function delete(string $id): bool
    {
        return $this->store->delete($id);
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

        $isAnswer = $task->answer !== null && $task->draft !== null;
        $round = $isAnswer ? $task->draft->session->clarifications + 1 : 0;
        // steps of an interrupted run of this round are replaced by the new run
        $task->steps = array_values(array_filter($task->steps, fn (Step $step): bool => $step->round < $round));
        $progress = fn (Step $step) => $this->record($task, $step);

        try {
            $manager = ($this->manager)();
            $task->draft = $isAnswer
                ? $manager->clarify($task->draft->session, $task->answer, $progress)
                : $manager->create($task->input, $progress);
        } catch (Cancelled) {
            return $this->store->get($task->id) ?? $task;
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
     * @return Task Saved task, or the stored one when it was cancelled meanwhile
     */
    private function finish(Task $task, Status $status, string $error = ''): Task
    {
        $task->status = $status;
        $task->error = $error;
        $task->attempts = $status === Status::Queued ? $task->attempts : 0;
        $task->updatedAt = time();

        return $this->save($task) ? $task : $this->store->get($task->id) ?? $task;
    }

    /**
     * Changes a stored task outside the worker.
     *
     * @param string $id Task id
     * @param Closure(Task): void $change Changes the task
     * @return bool Whether the task exists
     */
    private function change(string $id, Closure $change): bool
    {
        $task = $this->store->get($id);
        if ($task === null) {
            return false;
        }

        $change($task);
        $this->store->set($task);

        return true;
    }

    /**
     * Adds a step to the running task and saves it; `updatedAt` doubles as the worker's heartbeat.
     * A finished step replaces its running record.
     *
     * @param Task $task Running task
     * @param Step $step Started or finished step
     * @throws Cancelled When the task was cancelled or deleted, to stop the round
     */
    private function record(Task $task, Step $step): void
    {
        $last = array_key_last($task->steps);
        if ($step->isDone && $last !== null && ! $task->steps[$last]->isDone) {
            $task->steps[$last] = $step;
        } else {
            $task->steps[] = $step;
        }

        $task->updatedAt = time();
        if (! $this->save($task)) {
            throw new Cancelled('The task was cancelled.');
        }
    }

    /**
     * Saves a task the worker runs unless it was cancelled or deleted meanwhile.
     * Keeps the title and archive flag the user may have changed while the worker ran.
     *
     * @param Task $task Running task
     * @return bool Whether the task was saved
     */
    private function save(Task $task): bool
    {
        $stored = $this->store->get($task->id);
        if ($stored === null || $stored->status === Status::Cancelled) {
            return false;
        }

        $task->title = $stored->title;
        $task->isArchived = $stored->isArchived;
        $this->store->set($task);

        return true;
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
