<?php

declare(strict_types=1);

namespace App\Api\Tables;

use App\Dashboard\Pages\Tables;
use Expansa\Http\Request;
use Expansa\Http\Response;
use Expansa\Support\Arr;

final class TablesService
{
    /**
     * Render the rows of a page of a table of the `edit` page filtered by the request, and its pagination.
     *
     * @param Request  $request  The query of the page: `table`, `view`, `status`, `s`, `page` and the filters, see Table::activeFilters().
     * @param Response $response
     * @return Response The rows and the pagination in place of the current ones.
     */
    public function filter(Request $request, Response $response): Response
    {
        $table = Tables::data($request)['table']
            ->filterBy($request->post)
            ->paginate($request->getInt('page', 1));

        return $response
            ->update('.table-body', view('parts/table/table-row', ['table' => $table])->render())
            ->update('.table-header-pagination', view('parts/table/pagination', ['table' => $table, 'query' => Arr::exclude($request->post, ['nonce'])])->render());
    }
}
