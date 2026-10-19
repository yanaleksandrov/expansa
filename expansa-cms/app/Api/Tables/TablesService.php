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
     * Render the rows of a page of a table of the `edit` page filtered by the request, and the bar of its header:
     * the tools and the bulk actions follow the part of the list, e.g. the trash, the pagination the rows.
     *
     * @param Request  $request  The query of the page: `table`, the parameters of the parts of the list, `s`, `page`
     *                           and the filters, see Table::headData() and Table::activeFilters().
     * @param Response $response
     * @return Response The rows and the bar in place of the current ones.
     */
    public function filter(Request $request, Response $response): Response
    {
        $table = Tables::data($request)['table']
            ->filterBy($request->post)
            ->paginate($request->getInt('page', 1));

        // the filter stays on the page: the search being typed and the open popover are kept
        $header = view('parts/table/header', [
            ...$table->headData(),
            'table'  => $table,
            'query'  => Arr::exclude($request->post, ['nonce']),
            'filter' => false,
        ]);

        return $response
            ->update('.table-body', view('parts/table/table-row', ['table' => $table])->render())
            ->replace('.table-header', $header->render());
    }
}
