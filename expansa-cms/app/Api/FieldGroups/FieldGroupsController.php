<?php

declare(strict_types=1);

namespace App\Api\FieldGroups;

use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class FieldGroupsController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private FieldGroupsService $service = new FieldGroupsService(),
    ) {} // phpcs:ignore

    public function index(): Response
    {
        return $this->service->index();
    }

    public function create(Request $request): Response
    {
        return $this->service->create($request->post());
    }

    public function update(Request $request): Response
    {
        return $this->service->update($request->post());
    }

    public function delete(Request $request): Response
    {
        return $this->service->delete($request->post());
    }
}
