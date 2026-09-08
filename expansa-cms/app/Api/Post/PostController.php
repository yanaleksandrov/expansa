<?php

declare(strict_types=1);

namespace App\Api\Post;

use Expansa\Http\Request;

final readonly class PostController
{
    public function __construct(private PostService $service = new PostService())
    {
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function index(): array
    {
        return ['method' => 'PUT update user by ID'];
    }

    public function create(Request $request): array
    {
        return $this->service->create($request->post());
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
}
