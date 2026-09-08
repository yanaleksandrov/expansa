<?php

declare(strict_types=1);

namespace App\Api\Posts;

use Expansa\Http\Request;
use Expansa\Http\Response;

/**
 * Note: the dashboard also calls `posts/filter` (dashboard/forms/posts-filter.php and
 * others) — there is no PostsService::filter() to migrate, the legacy class never had
 * one either. Not implemented here; flagging rather than inventing the behavior.
 */
final readonly class PostsController
{
    public function __construct(private PostsService $service = new PostsService())
    {
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function create(): array
    {
        return ['method' => 'POST create user'];
    }

    public function index(): array
    {
        return $this->service->list();
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function update(): array
    {
        return ['method' => 'PUT update user by ID'];
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function delete(): array
    {
        return ['method' => 'DELETE remove user by ID'];
    }

    public function export(Request $request): Response
    {
        return $this->service->export($request->all());
    }

    public function import(Request $request): array
    {
        return $this->service->import($request->post());
    }
}
