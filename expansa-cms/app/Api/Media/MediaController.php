<?php

declare(strict_types=1);

namespace App\Api\Media;

use Expansa\Http\Request;

final readonly class MediaController
{
    public function __construct(private MediaService $service = new MediaService())
    {
    }

    public function get(): array
    {
        return $this->service->list();
    }

    public function upload(): array
    {
        return $this->service->upload($_FILES ?? []);
    }

    public function grab(Request $request): array
    {
        return $this->service->grab($request->post('urls', ''));
    }
}
