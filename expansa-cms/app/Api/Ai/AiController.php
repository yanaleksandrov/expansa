<?php

declare(strict_types=1);

namespace App\Api\Ai;

use Expansa\Http\Request;

/**
 * Plugin generation chat: POST /api/ai/{method}, the task id in the `id` field.
 * The dashboard polls `get` while the task runs and shows its steps as they finish.
 */
final readonly class AiController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private AiService $service = new AiService(),
    ) {}

    /**
     * Lists the tasks of the current user, newest first, without their messages.
     */
    public function index(): array
    {
        return $this->service->index();
    }

    /**
     * Returns one task with its messages and steps.
     */
    public function get(Request $request): array
    {
        return $this->service->get((string) ($request->post['id'] ?? ''));
    }

    /**
     * Starts a task from the `message` request.
     */
    public function create(Request $request): array
    {
        return $this->service->create((string) ($request->post['message'] ?? ''));
    }

    /**
     * Sends the `message` answer to the task's questions.
     */
    public function clarify(Request $request): array
    {
        return $this->service->clarify((string) ($request->post['id'] ?? ''), (string) ($request->post['message'] ?? ''));
    }

    /**
     * Stops a queued or running task.
     */
    public function cancel(Request $request): array
    {
        return $this->service->cancel((string) ($request->post['id'] ?? ''));
    }

    /**
     * Renames a task to `title`.
     */
    public function rename(Request $request): array
    {
        return $this->service->rename((string) ($request->post['id'] ?? ''), (string) ($request->post['title'] ?? ''));
    }

    /**
     * Archives a task.
     */
    public function archive(Request $request): array
    {
        return $this->service->archive((string) ($request->post['id'] ?? ''));
    }

    /**
     * Deletes a task; a running worker stops.
     */
    public function delete(Request $request): array
    {
        return $this->service->delete((string) ($request->post['id'] ?? ''));
    }
}
