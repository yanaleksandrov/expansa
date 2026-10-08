<?php

declare(strict_types=1);

namespace Expansa\Ai\Contracts;

use Expansa\Ai\Task;

interface Store
{
    /**
     * Returns a task by id.
     *
     * @param string $id Task id
     * @return Task|null Task, or null when it does not exist
     */
    public function get(string $id): ?Task;

    /**
     * Saves a task, replacing the stored version.
     *
     * @param Task $task Task to save
     */
    public function set(Task $task): void;

    /**
     * Deletes a task.
     *
     * @param string $id Task id
     * @return bool Whether the task existed
     */
    public function delete(string $id): bool;

    /**
     * Lists tasks, newest first.
     *
     * @param string $owner Only the tasks of this owner; empty lists all
     * @return Task[] Tasks
     */
    public function all(string $owner = ''): array;

    /**
     * Atomically takes the oldest queued task, or a running one idle longer than the timeout,
     * marks it running, and counts the attempt; concurrent workers never get the same task.
     *
     * @param int $timeout Seconds after which a running task counts as dead
     * @param string|null $id Take only this task
     * @return Task|null Claimed task, or null when none is available
     */
    public function claim(int $timeout, ?string $id = null): ?Task;
}
