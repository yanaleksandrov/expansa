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

    #[Can('types_edit')]
    public function create(Request $request): array
    {
        return $this->service->create($request->post);
    }
}
