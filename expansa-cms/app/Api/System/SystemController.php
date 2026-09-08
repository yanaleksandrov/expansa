<?php

declare(strict_types=1);

namespace App\Api\System;

use Expansa\Http\Request;

/**
 * Reference controller: thin HTTP layer only.
 *
 * `System` isn't a CRUD resource (no list/show/update/delete of "a system"), so its
 * two actions stay RPC-style routes (`POST /api/system/test`, `POST /api/system/install`)
 * rather than being forced into REST verbs. The controller's only job is translating
 * the Request into plain arguments for the service and returning its result — no
 * business logic, no direct model access, no manual JSON encoding.
 */
final readonly class SystemController
{
    public function __construct(
        private SystemService $service = new SystemService())
    {} // phpcs:ignore

    /**
     * @url POST /api/system/test
     */
    public function test(Request $request): array
    {
        return $this->service->checkRequirements($request->post());
    }

    /**
     * @url POST /api/system/install
     */
    public function install(Request $request): array
    {
        return $this->service->install($request->post());
    }
}
