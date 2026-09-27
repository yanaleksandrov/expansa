<?php

declare(strict_types=1);

namespace Expansa\Ai\Commands;

use Closure;
use Expansa\Ai\Enums\Status;
use Expansa\Ai\Queue;
use Expansa\Console\Commands\AbstractCommand;

/**
 * The `ai:work` command: runs queued generation tasks in the background.
 * With `--id` it runs one task, as started right after a request; without it, it empties the queue,
 * as the scheduler does every minute for tasks no worker started or whose worker crashed.
 * Exits with code 1 if a task failed.
 */
final class Work extends AbstractCommand
{
    public string $name = 'ai:work';

    public string $signature = 'ai:work [--id=<id>]';

    /**
     * Stores the queue factory.
     */
    public function __construct(

        /**
         * Returns the configured queue; bootstrap.php passes it.
         *
         * @var Closure(): Queue
         */
        private readonly Closure $queue,
    ) {}

    /**
     * Creates the `Queue::$start` callback that runs `ai:work --id=<id>` as a detached process.
     * Under PHP-FPM `PHP_BINARY` is the FPM binary, so pass the path of the PHP CLI.
     *
     * @param string $artisan Path of the artisan script
     * @param string $php PHP CLI binary
     * @return Closure(string): void Launcher for a task id
     */
    public static function createLauncher(string $artisan, string $php = 'php'): Closure
    {
        return function (string $id) use ($artisan, $php): void {
            $command = implode(' ', array_map(escapeshellarg(...), [$php, $artisan, 'ai:work', "--id={$id}"]));
            if (PHP_OS_FAMILY === 'Windows') {
                pclose(popen("start \"\" /B {$command} > NUL 2>&1", 'r'));
            } else {
                exec("{$command} > /dev/null 2>&1 &");
            }
        };
    }

    public function getDescription(): string
    {
        return t('Run the queued AI generation tasks.');
    }

    public function handle(): void
    {
        $queue = ($this->queue)();
        $id = (string) $this->console->option('id') ?: null;

        $failed = false;
        $processed = 0;
        while (($task = $queue->work($id)) !== null) {
            $processed++;
            $failed = $failed || $task->status === Status::Failed;
            $this->info(t('[green]#%s# %s %s', $task->status->value, $task->id, $task->error));

            // one task per run with --id; a queued retry is left to the next scheduled run
            if ($id !== null || $task->status === Status::Queued) {
                break;
            }
        }

        if ($processed === 0) {
            $this->info(t('No AI tasks are queued.'));
        }

        if ($failed) {
            exit(1);
        }
    }
}
