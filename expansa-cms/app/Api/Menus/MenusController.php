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

    #[Can('manage_menus')]
    public function create(Request $request, Response $response): Response
    {
        return $this->service->create($request, $response);
    }

    #[Can('manage_menus')]
    public function update(Request $request, Response $response): Response
    {
        return $this->service->update($request, $response);
    }

    #[Can('manage_menus')]
    public function delete(Request $request, Response $response): Response
    {
        return $this->service->delete($request, $response);
    }

    /**
     * Posts and terms to add to a menu, see MenusService::posts().
     *
     * @param Request $request `type`, the key of the source or nothing for every one, and `query`, the typed text.
     * @return array{posts: array<int, array{id: int, title: string, type: string, kind: string, object: string}>}
     */
    #[Can('manage_menus')]
    public function posts(Request $request): array
    {
        return ['posts' => $this->service->posts($request->getString('type'), $request->getString('query'))];
    }

    /**
     * Menu of a theme location as a tree, for a headless site: `GET /api/menus/<location>`, public.
     *
     * @param Response $response
     * @param string   $location Key of the location, from the URL.
     * @return Response
     */
    public function location(Response $response, string $location = ''): Response
    {
        return $this->service->location($response, $location);
    }
}
