<?php

declare(strict_types=1);

namespace App\Api\Files;

final readonly class FilesController
{
    public function __construct(private FilesService $service = new FilesService())
    {
    }

    public function upload(): array
    {
        return $this->service->upload($_FILES ?? []);
    }
}
