<?php

declare(strict_types=1);

namespace App\Api\Ai;

use App\Models\User;
use App\Support\Ai;
use Expansa\Ai\Enums\Stage;
use Expansa\Ai\Enums\Status;
use Expansa\Ai\Step;
use Expansa\Ai\Task;
use Expansa\Facades\Access;
use Expansa\Http\Exceptions\HttpError;
use Expansa\Http\Exceptions\NotFound;

/**
 * Plugin generation tasks of the current user, shaped for the dashboard chat.
 * A task becomes a list of messages: the request, the steps of each round, answers, and the result.
 * Generating plugins needs the `plugins_install` permission: the result is code for the site.
 */
final class AiService
{
    /**
     * Share of the progress bar a round reaches when a step of this stage starts.
     */
    private const array PROGRESS = [
        'context'    => 5,
        'analysis'   => 15,
        'tool'       => 30,
        'refinement' => 40,
        'generation' => 55,
        'validation' => 85,
    ];

    /**
     * Lists the tasks of the current user that are not archived, newest first.
     *
     * @return array{tasks: array<int, array<string, mixed>>, configured: bool}
     */
    public function index(): array
    {
        $tasks = array_filter(Ai::queue()->store->all($this->owner()), fn (Task $task): bool => ! $task->isArchived);

        return [
            'tasks'      => array_values(array_map($this->summary(...), $tasks)),
            'configured' => Ai::isConfigured(),
        ];
    }

    /**
     * Returns a task with its messages.
     *
     * @param string $id Task id
     * @return array<string, mixed>
     * @throws NotFound When the task does not exist or belongs to another user
     */
    public function get(string $id): array
    {
        return $this->present($this->find($id));
    }

    /**
     * Queues a new task and starts its worker.
     *
     * @param string $message User's request
     * @return array<string, mixed>
     * @throws HttpError When the request is empty or no AI service is configured
     */
    public function create(string $message): array
    {
        $owner = $this->owner();
        if (trim($message) === '') {
            throw new HttpError(422, t_attr('Describe the feature you need.'));
        }
        if (! Ai::isConfigured()) {
            throw new HttpError(422, t_attr('The AI service is not configured: add the service key to EX_AI in env.php.'));
        }

        return $this->present(Ai::queue()->dispatch($message, $owner));
    }

    /**
     * Queues the answer to the task's questions.
     *
     * @param string $id Task id
     * @param string $message User's answer
     * @return array<string, mixed>
     * @throws HttpError When the answer is empty or the task asks nothing
     */
    public function clarify(string $id, string $message): array
    {
        $task = $this->find($id);
        if (trim($message) === '') {
            throw new HttpError(422, t_attr('Write an answer to the questions.'));
        }
        if ($task->status !== Status::Questions) {
            throw new HttpError(409, t_attr('The task does not wait for an answer.'));
        }

        return $this->present(Ai::queue()->clarify($task->id, $message));
    }

    /**
     * Cancels a task.
     *
     * @param string $id Task id
     * @return array<string, mixed>
     */
    public function cancel(string $id): array
    {
        $task = $this->find($id);
        Ai::queue()->cancel($task->id);

        return $this->get($task->id);
    }

    /**
     * Renames a task.
     *
     * @param string $id Task id
     * @param string $title New name, empty shows the request
     * @return array<string, mixed>
     */
    public function rename(string $id, string $title): array
    {
        $task = $this->find($id);
        Ai::queue()->rename($task->id, mb_substr(strip_tags($title), 0, 120));

        return $this->summary($this->find($id));
    }

    /**
     * Archives a task.
     *
     * @param string $id Task id
     * @return array{id: string}
     */
    public function archive(string $id): array
    {
        Ai::queue()->archive($this->find($id)->id);

        return ['id' => $id];
    }

    /**
     * Deletes a task.
     *
     * @param string $id Task id
     * @return array{id: string}
     */
    public function delete(string $id): array
    {
        Ai::queue()->delete($this->find($id)->id);

        return ['id' => $id];
    }

    /**
     * Returns the id of the current user after checking the permission.
     */
    private function owner(): string
    {
        $user = User::current();
        Access::authorize($user, 'plugins_install');

        return (string) $user->id;
    }

    /**
     * Returns a task of the current user.
     *
     * @param string $id Task id
     * @throws NotFound When the task does not exist or belongs to another user
     */
    private function find(string $id): Task
    {
        $task = Ai::queue()->get($id);
        if ($task === null || $task->owner !== $this->owner()) {
            throw new NotFound(t_attr('The task is not found.'));
        }

        return $task;
    }

    /**
     * Describes a task for the process list.
     *
     * @param Task $task Task
     * @return array<string, mixed>
     */
    private function summary(Task $task): array
    {
        $last = end($task->steps) ?: null;
        $progress = match (true) {
            ! $task->status->isPending() && $task->status !== Status::Questions => 100,
            $last === null                                                      => 0,
            default => self::PROGRESS[$last->stage->value] + ($last->isDone ? 5 : 0),
        };

        return [
            'id'        => $task->id,
            'title'     => $task->title !== '' ? $task->title : mb_strimwidth($task->input, 0, 60, '…'),
            'status'    => $task->status->value,
            'isPending' => $task->status->isPending(),
            'progress'  => $progress,
            'createdAt' => $task->createdAt,
            'updatedAt' => $task->updatedAt,
        ];
    }

    /**
     * Describes a task with its messages for the chat.
     *
     * @param Task $task Task
     * @return array<string, mixed>
     */
    private function present(Task $task): array
    {
        // the answer to round r follows its last step; the answer the worker has not finished is not in the session yet
        $answers = $task->draft?->session->answers ?? [];
        if ($task->answer !== null) {
            $answers[count($answers)] = $task->answer;
        }

        $messages = [['role' => 'user', 'text' => $task->input, 'time' => $task->createdAt]];
        foreach ($task->steps as $i => $step) {
            $messages[] = $this->step($step, $task);

            $next = $task->steps[$i + 1] ?? null;
            if (($next === null || $next->round > $step->round) && isset($answers[$step->round])) {
                $messages[] = ['role' => 'user', 'text' => $answers[$step->round], 'time' => $next->time ?? $task->updatedAt];
            }
        }

        $result = $this->result($task);
        if ($result !== null) {
            $messages[] = $result;
        }

        return [...$this->summary($task), 'messages' => $messages];
    }

    /**
     * Describes a step: what runs, or what it produced.
     *
     * @param Step $step Started or finished step
     * @param Task $task Task of the step, to tell an interrupted step from a running one
     * @return array<string, mixed>
     */
    private function step(Step $step, Task $task): array
    {
        $data = $step->data;
        $running = ! $step->isDone && $task->status->isPending();
        $failed = ! empty($data['failed']) || isset($data['error']) || ! empty($data['errors']) || (! $step->isDone && ! $running);
        $details = [];

        $label = match ($step->stage) {
            Stage::Context    => $step->isDone ? t_attr('Read the documentation') : t_attr('Reading the documentation'),
            Stage::Analysis   => $step->isDone ? t_attr('Analyzed the request') : t_attr('Analyzing the request'),
            Stage::Tool       => t_attr('Tool %s', (string) ($data['name'] ?? '')),
            Stage::Refinement => $step->isDone ? t_attr('Refined the specification') : t_attr('Refining the specification'),
            Stage::Generation => match (true) {
                ($data['repair'] ?? 0) > 0 && $step->isDone => t_attr('Fixed the errors, attempt %d', (int) $data['repair']),
                ($data['repair'] ?? 0) > 0                  => t_attr('Fixing the errors, attempt %d', (int) $data['repair']),
                $step->isDone                               => t_attr('Wrote the plugin files'),
                default                                     => t_attr('Writing the plugin files'),
            },
            Stage::Validation => match (true) {
                ! $step->isDone         => t_attr('Checking the code'),
                $data['errors'] === []  => t_attr('The code passed the checks'),
                default                 => t_attr('Found errors: %d', count($data['errors'])),
            },
        };

        if (isset($data['files'])) {
            foreach ($data['files'] as $path => $bytes) {
                $details[] = sprintf('%s · %s', $path, $this->size((int) $bytes));
            }
        }
        if ($step->stage === Stage::Validation && ! empty($data['errors'])) {
            $details = array_slice($data['errors'], 0, 10);
        }
        if (isset($data['error'])) {
            $details[] = (string) $data['error'];
        }
        if (! $step->isDone && ! $running) {
            $details[] = t_attr('Interrupted');
        }

        return [
            'role'          => 'step',
            'stage'         => $step->stage->value,
            'label'         => $label,
            'text'          => (string) ($data['message'] ?? ''),
            'specification' => (string) ($data['specification'] ?? ''),
            'questions'     => $data['questions'] ?? [],
            'details'       => $details,
            'running'       => $running,
            'failed'        => $failed,
            'duration'      => $step->duration === null ? null : round($step->duration / 1000, 1),
            'tokens'        => $data['tokens'] ?? null,
            'time'          => $step->time,
        ];
    }

    /**
     * Describes the outcome of a finished round, null while the task runs.
     *
     * @param Task $task Task
     * @return array<string, mixed>|null
     */
    private function result(Task $task): ?array
    {
        $draft = $task->draft;
        $message = ['role' => 'assistant', 'kind' => $task->status->value, 'time' => $task->updatedAt, 'files' => [], 'errors' => []];

        // the generator's last note about the files
        $note = '';
        foreach (array_reverse($task->steps) as $step) {
            if ($step->stage === Stage::Generation && $step->isDone && ($step->data['message'] ?? '') !== '') {
                $note = $step->data['message'];
                break;
            }
        }

        return match ($task->status) {
            Status::Queued, Status::Running => null,
            Status::Questions => [...$message, 'text' => t_attr('Answer the questions above to continue.')],
            Status::Ready     => [
                ...$message,
                'text'          => $note ?: t_attr('The plugin is ready. Review the files before installing it.'),
                'specification' => $draft->specification,
                'files'         => $this->files($draft->files),
            ],
            Status::Invalid   => [
                ...$message,
                'text'   => t_attr('I wrote the files, but errors remain after the fixes.'),
                'files'  => $this->files($draft->files),
                'errors' => $draft->errors,
            ],
            Status::MissingExtensions => [
                ...$message,
                'text' => t_attr('The task needs PHP extensions that are not installed: %s. Install them or change the task.', implode(', ', $draft->missingExtensions)),
            ],
            Status::Failed    => [...$message, 'text' => t_attr('The generation failed.'), 'errors' => [$task->error]],
            Status::Cancelled => [...$message, 'text' => t_attr('Stopped.')],
        };
    }

    /**
     * Lists generated files with their size and source.
     *
     * @param array<string, string> $files Relative paths mapped to source
     * @return array<int, array{path: string, size: string, content: string}>
     */
    private function files(array $files): array
    {
        $list = [];
        foreach ($files as $path => $content) {
            $list[] = ['path' => $path, 'size' => $this->size(strlen($content)), 'content' => $content];
        }

        return $list;
    }

    /**
     * Formats a byte count.
     *
     * @param int $bytes Size in bytes
     */
    private function size(int $bytes): string
    {
        return $bytes < 1024 ? t_attr('%d B', $bytes) : t_attr('%s KB', number_format($bytes / 1024, 1));
    }
}
