<?php

declare(strict_types=1);

namespace App\Api\Translations;

use Expansa\Http\Request;

final readonly class TranslationsController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private TranslationsService $service = new TranslationsService(),
    )
    {
    }

    public function get(Request $request): array
    {
        return $this->service->get($request->post['project'] ?? '');
    }

    public function update(Request $request): array
    {
        return $this->service->update($request->post);
    }
}
