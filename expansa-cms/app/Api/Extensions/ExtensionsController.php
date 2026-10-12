<?php

declare(strict_types=1);

namespace App\Api\Extensions;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class ExtensionsController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private ExtensionsService $service = new ExtensionsService(),
    ) {}

    #[Can('plugins_activate')]
    public function activate(Request $request, Response $response): Response
    {
        return $this->service->activate((string) ($request->post['id'] ?? ''), $response);
    }

    #[Can('plugins_activate')]
    public function deactivate(Request $request, Response $response): Response
    {
        return $this->service->deactivate((string) ($request->post['id'] ?? ''), $response);
    }
}
