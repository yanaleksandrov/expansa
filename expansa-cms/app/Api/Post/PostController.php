<?php

declare(strict_types=1);

namespace App\Api\Post;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class PostController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private PostService $service = new PostService(),
    ) {}

    #[Can('types_edit')]
    public function create(Request $request, Response $response): Response
    {
        return $this->service->create($request, $response);
    }

    #[Can('types_edit')]
    public function update(Request $request, Response $response): Response
    {
        return $this->service->update($request, $response);
    }

    #[Can('types_edit')]
    public function authors(Request $request, Response $response): Response
    {
        return $this->service->authors($request, $response);
    }

    #[Can('types_edit')]
    public function parents(Request $request, Response $response): Response
    {
        return $this->service->parents($request, $response);
    }

    #[Can('types_edit')]
    public function duplicate(Request $request, Response $response): Response
    {
        return $this->service->duplicate($request, $response);
    }

    #[Can('types_edit')]
    public function restore(Request $request, Response $response): Response
    {
        return $this->service->restore($request, $response);
    }

    #[Can('types_edit')]
    public function delete(Request $request, Response $response): Response
    {
        return $this->service->delete($request, $response);
    }

    #[Can('types_edit')]
    public function emptyTrash(Request $request, Response $response): Response
    {
        return $this->service->emptyTrash($request, $response);
    }

    #[Can('types_edit')]
    public function bulk(Request $request, Response $response): Response
    {
        return $this->service->bulk($request, $response);
    }
}
