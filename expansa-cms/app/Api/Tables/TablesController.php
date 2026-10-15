<?php

declare(strict_types=1);

namespace App\Api\Tables;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class TablesController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private TablesService $service = new TablesService(),
    ) {}

    #[Can('read')]
    public function filter(Request $request, Response $response): Response
    {
        return $this->service->filter($request, $response);
    }
}
