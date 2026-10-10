<?php

declare(strict_types=1);

namespace App\Api\FieldGroups;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class FieldGroupsController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private FieldGroupsService $service = new FieldGroupsService(),
    ) {}

    #[Can('manage_options')]
    public function index(Response $response): Response
    {
        return $this->service->index($response);
    }

    #[Can('manage_options')]
    public function create(Request $request, Response $response): Response
    {
        return $this->service->create($request, $response);
    }

    #[Can('manage_options')]
    public function update(Request $request, Response $response): Response
    {
        return $this->service->update($request, $response);
    }

    #[Can('manage_options')]
    public function delete(Request $request, Response $response): Response
    {
        return $this->service->delete($request, $response);
    }
}
