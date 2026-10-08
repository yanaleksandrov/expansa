<?php

declare(strict_types=1);

namespace App\Api\Media;

use App\Http\Can;
use Expansa\Http\Request;

final readonly class MediaController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private MediaService $service = new MediaService(),
    ) {}

    #[Can('files_upload')]
    public function get(Request $request): array
    {
        return $this->service->list([
            'page' => max(1, $request->getInt('page', 1)),
            's'    => $request->getString('s'),
        ]);
    }

    #[Can('files_upload')]
    public function upload(Request $request): array
    {
        return $this->service->upload($request->files);
    }

    #[Can('files_upload')]
    public function grab(Request $request): array
    {
        return $this->service->grab($request->post['urls'] ?? '');
    }

    #[Can('files_delete')]
    public function delete(Request $request): array
    {
        return $this->service->delete((array) ($request->post['ids'] ?? []));
    }
}
