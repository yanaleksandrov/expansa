<?php

declare(strict_types=1);

namespace App\Api\Options;

use Expansa\Http\Request;

final readonly class OptionsController
{
    public function __construct(private OptionsService $service = new OptionsService())
    {
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function create(): array
    {
        return ['method' => 'POST create user'];
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function index(): array
    {
        return ['method' => 'GET user list'];
    }

    public function update(Request $request): array
    {
        return $this->service->update($request->post());
    }

    /** @todo not implemented — placeholder carried over from the legacy class */
    public function delete(): array
    {
        return ['method' => 'DELETE remove user by ID'];
    }
}
