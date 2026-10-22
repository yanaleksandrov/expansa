<?php

declare(strict_types=1);

namespace App\Api\Menus;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class MenusController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private MenusService $service = new MenusService(),
    ) {}

    #[Can('manage_options')]
    public function create(Request $request, Response $response): Response
    {
        return $this->service->create($request, $response);
    }

    #[Can('manage_options')]
    public function update(Request $request, Response $response): Response
    {
        return $this->service->update($request, $response);
    }

    #[Can('manage_options')]
    public function delete(Request $request, Response $response): Response
    {
        return $this->service->delete($request, $response);
    }

    /**
     * Posts to add to a menu, see MenusService::posts().
     *
     * @param Request $request `type`, the key of the post type or nothing for every type, and `query`, the typed text.
     * @return array{posts: array<int, array{id: int, title: string, type: string}>}
     */
    #[Can('manage_options')]
    public function posts(Request $request): array
    {
        return ['posts' => $this->service->posts($request->getString('type'), $request->getString('query'))];
    }
}
