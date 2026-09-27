<?php

declare(strict_types=1);

namespace Expansa\Ai\Stores;

use Expansa\Ai\Contracts\Store;
use Expansa\Ai\Draft;
use Expansa\Ai\Enums\Status;
use Expansa\Ai\Session;
use Expansa\Ai\Task;
use InvalidArgumentException;

/**
 * Keeps tasks as files in a directory: one serialized task per file, a lock file for claims.
 * Needs no database and suits a single server; many tasks or several servers need a database store.
 */
final class File implements Store
{
    /**
     * Classes a task file may contain; anything else is not restored.
     */
    private const array CLASSES = [Task::class, Draft::class, Session::class, Status::class];

    /**
     * Stores the task directory, created on the first write.
     */
    public function __construct(

        /**
         * Directory of task files, with a trailing slash.
         */
        public private(set) string $directory {
            set => rtrim($value, '/\\') . '/';
        },
    ) {}

    /**
     * Returns a task by id.
     *
     * @param string $id Task id
     * @return Task|null Task, or null when it does not exist or its file is damaged
     */
    public function get(string $id): ?Task
    {
        $path = $this->path($id);
        if ($path === null || ! is_file($path)) {
            return null;
        }

        $task = unserialize((string) file_get_contents($path), ['allowed_classes' => self::CLASSES]);

        return $task instanceof Task ? $task : null;
    }

    /**
     * Saves a task; the file is replaced at once, so readers never see a partial write.
     *
     * @param Task $task Task to save
     */
    public function set(Task $task): void
    {
        $path = $this->path($task->id) ?? throw new InvalidArgumentException("Invalid task id: {$task->id}");
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }

        $temporary = $path . '.' . bin2hex(random_bytes(4));
        file_put_contents($temporary, serialize($task));
        rename($temporary, $path);
    }

    /**
     * Deletes a task file.
     *
     * @param string $id Task id
     * @return bool Whether the task existed
     */
    public function delete(string $id): bool
    {
        $path = $this->path($id);

        return $path !== null && is_file($path) && unlink($path);
    }

    /**
     * Lists tasks, newest first.
     *
     * @param string $owner Only the tasks of this owner; empty lists all
     * @return Task[] Tasks
     */
    public function all(string $owner = ''): array
    {
        $tasks = [];
        foreach (glob($this->directory . '*.task') ?: [] as $path) {
            $task = $this->get(basename($path, '.task'));
            if ($task !== null && ($owner === '' || $task->owner === $owner)) {
                $tasks[] = $task;
            }
        }

        usort($tasks, fn (Task $a, Task $b): int => [$b->createdAt, $b->id] <=> [$a->createdAt, $a->id]);

        return $tasks;
    }

    /**
     * Takes the oldest available task under an exclusive lock, so concurrent workers never share one.
     *
     * @param int $timeout Seconds after which a running task counts as dead
     * @param string|null $id Take only this task
     * @return Task|null Claimed task, or null when none is available
     */
    public function claim(int $timeout, ?string $id = null): ?Task
    {
        if (! is_dir($this->directory)) {
            return null;
        }

        $lock = fopen($this->directory . '.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $candidates = $id === null ? array_reverse($this->all()) : array_filter([$this->get($id)]);
            foreach ($candidates as $task) {
                $isDead = $task->status === Status::Running && $task->updatedAt < time() - $timeout;
                if ($task->status === Status::Queued || $isDead) {
                    $task->status = Status::Running;
                    $task->attempts++;
                    $task->updatedAt = time();
                    $this->set($task);

                    return $task;
                }
            }

            return null;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Builds the file path of a task id; ids with other characters could leave the directory.
     *
     * @param string $id Task id
     * @return string|null File path, or null for an invalid id
     */
    private function path(string $id): ?string
    {
        return preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $id) ? "{$this->directory}{$id}.task" : null;
    }
}
