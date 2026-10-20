<?php

declare(strict_types=1);

namespace App\Api\Search;

use App\Http\Can;
use Expansa\Http\Request;

final readonly class SearchController
{
    public function __construct(

        /**
         * Endpoint business logic; the default lets Kernel::dispatch() create the controller without arguments.
         */
        private SearchService $service = new SearchService(),
    ) {}

    /**
     * Results of the search box of the dashboard for the typed text, see SearchService::find().
     *
     * @param Request $request `query`, the typed text.
     * @return array{links: array<int, array{url: string, text: string}>}
     */
    #[Can('read')]
    public function find(Request $request): array
    {
        return ['links' => $this->service->find($request->getString('query'))];
    }
}
