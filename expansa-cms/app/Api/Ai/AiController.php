<?php

declare(strict_types=1);

namespace App\Api\Ai;

use App\Http\Can;
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
    #[Can('plugins_install')]
    public function index(): array
    {
        return $this->service->index();
    }

    /**
     * Returns one task with its messages and steps.
     */
    #[Can('plugins_install')]
    public function get(Request $request): array
    {
        return $this->service->get($request);
    }

    /**
     * Starts a task from the `message` request.
     */
    #[Can('plugins_install')]
    public function create(Request $request): array
    {
        return $this->service->create($request);
    }

    /**
     * Sends the `message` answer to the task's questions.
     */
    #[Can('plugins_install')]
    public function clarify(Request $request): array
    {
        return $this->service->clarify($request);
    }

    /**
     * Stops a queued or running task.
     */
    #[Can('plugins_install')]
    public function cancel(Request $request): array
    {
        return $this->service->cancel($request);
    }

    /**
     * Renames a task to `title`.
     */
    #[Can('plugins_install')]
    public function rename(Request $request): array
    {
        return $this->service->rename($request);
    }

    /**
     * Archives a task.
     */
    #[Can('plugins_install')]
    public function archive(Request $request): array
    {
        return $this->service->archive($request);
    }

    /**
     * Deletes a task; a running worker stops.
     */
    #[Can('plugins_install')]
    public function delete(Request $request): array
    {
        return $this->service->delete($request);
    }
}
