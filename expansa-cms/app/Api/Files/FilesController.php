<?php

declare(strict_types=1);

namespace App\Api\Files;

use Expansa\Http\Request;

final readonly class FilesController
{
    public function __construct(private FilesService $service = new FilesService())
    {
    }

    public function upload(Request $request): array
    {
        return $this->service->upload($_FILES ?? [], $request->post('encoding', 'auto'));
    }
}
