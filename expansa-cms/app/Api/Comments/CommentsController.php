<?php

declare(strict_types=1);

namespace App\Api\Comments;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class CommentsController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private CommentsService $service = new CommentsService(),
    ) {}

    #[Can('manage_comments')]
    public function update(Request $request, Response $response): Response
    {
        return $this->service->update($request, $response);
    }

    #[Can('manage_comments')]
    public function delete(Request $request, Response $response): Response
    {
        return $this->service->delete($request, $response);
    }

    #[Can('manage_comments')]
    public function add(Request $request, Response $response): Response
    {
        return $this->service->add($request, $response);
    }

    #[Can('manage_comments')]
    public function reply(Request $request, Response $response): Response
    {
        return $this->service->reply($request, $response);
    }

    public function submit(Request $request, Response $response): Response
    {
        return $this->service->submit($request, $response);
    }

    #[Can('manage_comments')]
    public function bulk(Request $request, Response $response): Response
    {
        return $this->service->bulk($request, $response);
    }
}
