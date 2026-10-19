<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Closure;
use Expansa\Builders\Table\Cell;
use LogicException;

/**
 * Base of a dashboard table: a subclass returns the rows in data() and describes the columns
 * in cells() with cell(). The template renders a row cell by cell, each with the view of its column,
 * see Table\Cell for the variables of the view. The rows that match the filters are shown by pages
 * of perPage(), the page is the `page` query parameter or set by paginate(). A filter is a query parameter
 * of its field with comma-separated values, `!` after the name for "is not": `?author=1,3&role!=editor`.
 *
 * @package Expansa\Builders
 */
abstract class Table
{
    /**
     * File that enqueues the items filter form, included by every table.
     */
    private static string $filter = '';

    /**
     * Rows on a page, or the function that gets them on the first call; 0 shows all rows on one page.
     */
    private static Closure|int $perPage = 20;

    /**
     * Query parameters of the page the filters must not take, `page` among them.
     *
     * @var string[]
     */
    private static array $reserved = ['page'];

    /**
     * Rows from data() that match the filters, null until rows() is called.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $rows = null;

    /**
     * Filters of the request, null until activeFilters() is called.
     *
     * @var array<int, array{field: string, operator: string, values: string[]}>|null
     */
    private ?array $active = null;

    /**
     * Query parameters set by filterBy(), null reads `$_GET`.
     */
    private ?array $input = null;

    /**
     * Requested page from 1, set by paginate(), null reads the `page` query parameter.
     */
    private ?int $page = null;

    /**
     * Columns, from cells().
     *
     * @var Cell[]
     */
    public readonly array $cells;

    /**
     * Inline style of the table and its rows: `--expansa-grid-template-columns` from the column widths.
     */
    public readonly string $style;

    /**
     * Set the file included before every table, the default view of the cells, the rows on a page
     * and the query parameters of the page the filters must not take.
     *
     * @param string      $filter   File that enqueues the items filter form.
     * @param string      $cellView Prefix of the cell views, e.g. `parts/table/cell`.
     * @param Closure|int $perPage  Rows on a page or the function returning them, e.g. from the settings.
     * @param string[]    $reserved Parameters of the page, e.g. `status` and `s`; `page` is always reserved.
     * @return void
     */
    public static function configure(
        string $filter = '',
        string $cellView = '',
        Closure|int $perPage = 20,
        array $reserved = [],
    ): void {
        self::$filter   = $filter;
        self::$perPage  = $perPage;
        self::$reserved = array_values(array_unique(['page', ...$reserved]));

        Cell::configure($cellView);
    }

    public function __construct()
    {
        if (self::$filter !== '') {
            require_once self::$filter;
        }

        $this->cells = $this->cells();
        $this->style = self::style($this->cells);
    }

    /**
     * Get the rows of the table; templates read them through getData().
     *
     * @return array<int, array<string, mixed>>
     */
    abstract public function data(): array;

    /**
     * Get the rows of the current page: data() runs on the first call, so creating a table runs no queries.
     * Rows that do not match the active filters are left out.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getData(): array
    {
        $perPage = $this->perPage();

        return $perPage > 0 ? array_slice($this->rows(), ($this->page() - 1) * $perPage, $perPage) : $this->rows();
    }

    /**
     * Get the number of rows that match the filters on all pages.
     *
     * @return int
     */
    public function total(): int
    {
        return count($this->rows());
    }

    /**
     * Get the number of pages, 1 without rows.
     *
     * @return int
     */
    public function pages(): int
    {
        $perPage = $this->perPage();

        return $perPage > 0 ? max(1, (int) ceil($this->total() / $perPage)) : 1;
    }

    /**
     * Get the current page: the requested one within 1 and pages().
     *
     * @return int
     */
    public function page(): int
    {
        $page = $this->page ?? (int) ($_GET['page'] ?? 1);

        return min(max(1, $page), $this->pages());
    }

    /**
     * Show the given page instead of the `page` query parameter, e.g. of an API request.
     *
     * @param int $page From 1; out of the range shows the nearest page.
     * @return static
     */
    public function paginate(int $page): static
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get the rows on a page, those of configure() by default; 0 shows all rows on one page.
     *
     * @return int
     */
    public function perPage(): int
    {
        if (self::$perPage instanceof Closure) {
            self::$perPage = (int) (self::$perPage)();
        }

        return max(0, self::$perPage);
    }

    /**
     * Get the rows that match the filters, data() runs on the first call.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        if ($this->rows === null) {
            $filters    = $this->activeFilters();
            $this->rows = $filters ? array_values(array_filter($this->data(), fn (array $row) => self::matches($row, $filters))) : $this->data();
        }

        return $this->rows;
    }

    /**
     * Describe the fields the rows can be filtered by, keyed by the row key: `label`, `icon`, `options`
     * as value => label and `param`, the query parameter, the key by default. Without fields the table has no filter.
     *
     * @return array<string, array{label: string, icon?: string, param?: string, options: array<string|int, string>}>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Filter the rows by the given query parameters instead of `$_GET`, e.g. of an API request.
     *
     * @param array $query Parameters as `author => '1,3'` and `role! => 'editor'`.
     * @return static
     */
    public function filterBy(array $query): static
    {
        $this->input  = $query;
        $this->active = null;
        $this->rows   = null;

        return $this;
    }

    /**
     * Get the filters of the query, a field of filters() each: its parameter gives the `is` values,
     * the parameter with `!` the `not` ones; only the known values of the field count, a filter without them is skipped.
     *
     * @return array<int, array{field: string, operator: string, values: string[]}>
     * @throws LogicException When the parameter of a field is reserved by configure().
     */
    public function activeFilters(): array
    {
        if ($this->active !== null) {
            return $this->active;
        }

        $query = $this->input ?? $_GET;

        $this->active = [];
        foreach ($this->filters() as $field => $filter) {
            $param = (string) ($filter['param'] ?? $field);
            if (in_array($param, self::$reserved, true)) {
                throw new LogicException(sprintf('The filter "%s" of %s takes the reserved query parameter "%s".', $field, static::class, $param));
            }

            $options = array_map('strval', array_keys($filter['options'] ?? []));
            foreach (['is' => $param, 'not' => "$param!"] as $operator => $name) {
                $raw    = $query[$name] ?? '';
                $values = is_string($raw) ? array_values(array_intersect(explode(',', $raw), $options)) : [];
                if ($values !== []) {
                    $this->active[] = [
                        'field'    => $field,
                        'operator' => $operator,
                        'values'   => $values,
                    ];
                }
            }
        }

        return $this->active;
    }

    /**
     * Check a row against all filters: a scalar value is one of the values, a list has one of them.
     *
     * @param array<string, mixed> $row
     * @param array<int, array{field: string, operator: string, values: string[]}> $filters
     * @return bool
     */
    private static function matches(array $row, array $filters): bool
    {
        foreach ($filters as ['field' => $field, 'operator' => $operator, 'values' => $values]) {
            $found = array_intersect(array_map('strval', (array) ($row[$field] ?? [])), $values) !== [];
            if ($found === ($operator === 'not')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Describe the columns with cell().
     *
     * @return Cell[]
     */
    abstract public function cells(): array;

    /**
     * Get the data of the header above the table: title, badge and the tools to show, e.g. `empty`, the `endpoint`
     * and the `data` of the button emptying the trash; `parts` are the parts
     * of the list for the select of the filter by key, each a `label` and the `query` parameters that open it,
     * the parameters of the other parts are dropped; `part` is the key of the current one.
     *
     * @return array<string, mixed>
     */
    public function headData(): array
    {
        return [];
    }

    /**
     * Get the bulk actions of the selected rows, none by default: `endpoint` of the API they are posted to,
     * `actions` labels by value or groups of a select, `confirm` the values that ask first, `password`
     * the values that need the current password, `data` the fields sent with the IDs.
     *
     * @return array{
     *     endpoint?: string,
     *     actions?: array<string, mixed>,
     *     confirm?: string[],
     *     password?: string[],
     *     data?: array<string, scalar>,
     * }
     */
    public function bulk(): array
    {
        return [];
    }

    /**
     * Get the data of the state shown instead of the rows when there are none: icon, title, description.
     *
     * @return array<string, mixed>
     */
    public function notFoundData(): array
    {
        return [];
    }

    /**
     * Get the attributes of a link that posts to the API from a row, for the `rowAction` binding of the `table`
     * component: the data goes to the endpoint, a confirmation asks first, a refresh shows the rows again after it.
     *
     * @param string               $endpoint API route, e.g. `post/delete`.
     * @param array<string, mixed> $data     Fields posted with the request.
     * @param string               $confirm  Key of the text that asks first, e.g. `deleteItem`; empty asks nothing.
     * @param bool                 $refresh  Show the rows again after the response.
     * @return array<string, string>
     */
    protected static function action(string $endpoint, array $data = [], string $confirm = '', bool $refresh = false): array
    {
        return [
            'u-bind'        => 'rowAction',
            'data-endpoint' => $endpoint,
            'data-payload'  => (string) json_encode((object) $data),
            ...($confirm !== '' ? ['data-confirm' => $confirm] : []),
            ...($refresh ? ['data-refresh' => '1'] : []),
        ];
    }

    /**
     * Start a column description.
     *
     * @param string $key Key of the value in a row.
     * @return Cell
     */
    public function cell(string $key): Cell
    {
        return new Cell($key);
    }

    /**
     * Get the `--expansa-grid-template-columns` style from the column widths, a run of the same width as repeat().
     *
     * @param Cell[] $cells
     * @return string Empty without columns.
     */
    private static function style(array $cells): string
    {
        $widths = [];
        foreach ($cells as $cell) {
            $width    = trim($cell->width ?: '1fr');
            $widths[] = $cell->flexible ? "minmax($width, 1fr)" : $width;
        }

        $parts = [];
        $run   = 0;

        foreach ($widths as $i => $width) {
            $run++;
            if (($widths[$i + 1] ?? null) !== $width) {
                $parts[] = $run > 1 ? "repeat($run, $width)" : $width;
                $run     = 0;
            }
        }

        return $parts ? '--expansa-grid-template-columns: ' . implode(' ', $parts) : '';
    }
}
