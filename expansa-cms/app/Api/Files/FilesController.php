<?php

declare(strict_types=1);

namespace App\Api\Files;

use Expansa\Http\Request;

final readonly class FilesController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private FilesService $service = new FilesService(),
    )
    {
    }

    public function upload(Request $request): array
    {
        return $this->service->upload($request->files, $request->post['encoding'] ?? 'auto');
    }
}
