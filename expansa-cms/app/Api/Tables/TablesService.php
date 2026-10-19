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
     * Render the rows of a page of a table of the `edit` page filtered and sorted by the request, the bar of its header
     * and the column titles: the tools and the bulk actions follow the part of the list, e.g. the trash,
     * the pagination the rows, the titles the sorting.
     *
     * @param Request  $request  The query of the page: `table`, the parameters of the parts of the list, `s`, `page`,
     *                           `orderby`, `order` and the filters, see Table::headData(), Table::sorting() and Table::activeFilters().
     * @param Response $response
     * @return Response The rows, the bar and the titles in place of the current ones.
     */
    public function filter(Request $request, Response $response): Response
    {
        $table = Tables::data($request)['table']
            ->filterBy($request->post)
            ->paginate($request->getInt('page', 1));

        $query = Arr::exclude($request->post, ['nonce']);

        // the filter stays on the page: the search being typed and the open popover are kept
        $header = view('parts/table/table-header', [
            ...$table->headData(),
            'table'  => $table,
            'query'  => $query,
            'filter' => false,
        ]);

        // the column titles show the sorting
        $titles = '';
        foreach ($table->cells as $cell) {
            $titles .= view('parts/table/cell-head', ['cell' => $cell, 'sorting' => $table->sorting(), 'query' => $query])->render();
        }

        return $response
            ->update('.table-body', view('parts/table/table-row', ['table' => $table])->render())
            ->replace('.table-header', $header->render())
            ->update('.table-head-data', $titles);
    }
}
