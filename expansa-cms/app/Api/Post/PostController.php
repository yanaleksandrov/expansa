<?php

declare(strict_types=1);

namespace App\Api\Post;

use App\Http\Can;
use Expansa\Http\Request;

final readonly class PostController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private PostService $service = new PostService(),
    ) {}

    /**
 * @todo not implemented — placeholder carried over from the legacy class
*/
    #[Can('read')]
    public function index(): array
    {
        return ['method' => 'PUT update user by ID'];
    }

    #[Can('types_edit')]
    public function create(Request $request): array
    {
        return $this->service->create($request->post);
    }

    /**
 * @todo not implemented — placeholder carried over from the legacy class
*/
    #[Can('types_edit')]
    public function update(): array
    {
        return ['method' => 'PUT update user by ID'];
    }

    /**
 * @todo not implemented — placeholder carried over from the legacy class
*/
    #[Can('types_delete')]
    public function delete(): array
    {
        return ['method' => 'DELETE remove user by ID'];
    }
}
