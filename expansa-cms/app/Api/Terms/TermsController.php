<?php

declare(strict_types=1);

namespace App\Api\Terms;

use App\Http\Can;
use Expansa\Http\Request;
use Expansa\Http\Response;

final readonly class TermsController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private TermsService $service = new TermsService(),
    ) {}

    #[Can('manage_terms')]
    public function create(Request $request, Response $response): Response
    {
        return $this->service->create($request, $response);
    }

    #[Can('manage_terms')]
    public function update(Request $request, Response $response): Response
    {
        return $this->service->update($request, $response);
    }

    #[Can('manage_terms')]
    public function delete(Request $request, Response $response): Response
    {
        return $this->service->delete($request, $response);
    }

    #[Can('manage_terms')]
    public function bulk(Request $request, Response $response): Response
    {
        return $this->service->bulk($request, $response);
    }

    #[Can('manage_terms')]
    public function setDefault(Request $request, Response $response): Response
    {
        return $this->service->setDefault($request, $response);
    }

    #[Can('assign_terms')]
    public function search(Request $request, Response $response): Response
    {
        return $this->service->search($request, $response);
    }

    /**
     * Terms of a taxonomy as a tree, for a headless site: `GET /api/terms/<taxonomy>`, public.
     *
     * @param Response $response
     * @param string   $taxonomy Key of the taxonomy, from the URL.
     * @return Response
     */
    public function tree(Response $response, string $taxonomy = ''): Response
    {
        return $this->service->tree($response, $taxonomy);
    }
}
