<?php

declare(strict_types=1);

namespace App\Api\Posts;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

/**
 * Note: the dashboard also calls `posts/filter` (dashboard/forms/posts-filter.php and
 * others) — there is no PostsService::filter() to migrate, the legacy class never had
 * one either. Not implemented here; flagging rather than inventing the behavior.
 */
final readonly class PostsController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private PostsService $service = new PostsService(),
    ) {}

    #[Can('read')]
    public function index(): array
    {
        return $this->service->list();
    }

    #[Can('manage_export')]
    public function export(Request $request, Response $response): Response
    {
        return $this->service->export($request, $response);
    }

    #[Can('manage_import')]
    public function import(Request $request): array
    {
        return $this->service->import($request);
    }
}
