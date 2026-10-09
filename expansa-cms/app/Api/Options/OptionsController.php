<?php

declare(strict_types=1);

namespace App\Api\Options;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class OptionsController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private OptionsService $service = new OptionsService(),
    ) {}

    #[Can('manage_options')]
    public function update(Request $request, Response $response): Response
    {
        return $this->service->update($request, $response);
    }

    #[Can('manage_options')]
    public function mailTest(Response $response): Response
    {
        return $this->service->mailTest($response);
    }
}
