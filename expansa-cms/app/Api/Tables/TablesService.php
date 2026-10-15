<?php

declare(strict_types=1);

namespace App\Api\Tables;

use App\Dashboard\Pages\Tables;
use Expansa\Http\Request;
use Expansa\Http\Response;

final class TablesService
{
    /**
     * Render the rows of a table of the `edit` page filtered by the request.
     *
     * @param Request  $request  `table` and `view` as on the page, `filter` items with `field`, `operator` and `values`.
     * @param Response $response
     * @return Response The rows in place of the current ones.
     */
    public function filter(Request $request, Response $response): Response
    {
        $filter = $request->get('filter', []);
        $table  = Tables::data($request)['table']->filterBy(is_array($filter) ? $filter : []);

        return $response->update('.table-body', view('parts/table/table-row', ['table' => $table])->render());
    }
}
