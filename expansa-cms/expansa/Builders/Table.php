<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Closure;
use Expansa\Builders\Table\Cell;
use Expansa\Builders\Table\Queried;
use Expansa\Builders\Table\Values\Bulk;
use Expansa\Builders\Table\Values\Filter;
use Expansa\Builders\Table\Values\Sorting;
use Expansa\Database\Query\Raw;
use LogicException;
use Stringable;

/**
 * Base of a dashboard table: a subclass returns the rows in data() and describes the columns
 * in cells() with cell(). The template renders a row cell by cell, each with the view of its column,
 * see Table\Cell for the variables of the view. The rows that match the filters are shown by pages
 * of perPage(), the page is the `page` query parameter or set by paginate(). A filter is a query parameter
 * of its field with comma-separated values, `!` after the name for "is not": `?author=1,3&role!=editor`.
 * The rows are sorted by a sortable column of `orderby`, `order` is `asc` or `desc`, see sorting().
 * The filters and the sorting work on the rows of data() in PHP; a table of Table\Queried does them in its query
 * and reads only the rows of the page, so that they cover the whole table.
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
     * Number of the rows of a Table\Queried table that match the filters, null until total() is called.
     */
    private ?int $count = null;

    /**
     * Fields of filters(), read once: they may come from the database, e.g. the options of the authors.
     *
     * @var array<string, Filter>|null
     */
    private ?array $fields = null;

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
     * Get the rows of the table; templates read them through getData(). A table of Table\Queried
     * returns only the rows of the current page, read with paged().
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
        if ($this instanceof Queried) {
            return $this->rows ??= $this->data();
        }

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
        if ($this instanceof Queried) {
            return $this->count ??= $this->count();
        }

        return count($this->rows());
    }

    /**
     * Add the conditions of the active filters to the conditions of a query of a Table\Queried table,
     * e.g. for its count(); a filter is a group of its own, see filterWhere().
     *
     * @param array<string, mixed> $where Conditions of the table, e.g. of the status and the search.
     * @return array<string, mixed>
     */
    protected function filtered(array $where): array
    {
        $groups = [];
        foreach ($this->activeFilters() as $index => ['field' => $field, 'operator' => $operator, 'values' => $values]) {
            $groups["AND #filter $index"] = $this->filterWhere($field, $operator, $values);
        }

        return $groups === [] ? $where : [...$where, 'AND #filters' => $groups];
    }

    /**
     * Get the query of the rows of the current page of a Table\Queried table: the conditions of filtered(),
     * the order of orderBy() for the column of sorting() or the default one, and the limit of the page.
     *
     * @param array<string, mixed>  $where Conditions of the table.
     * @param array<string, string> $order Default order, e.g. `['id' => 'DESC']`, also for the rows equal by the sorting.
     * @return array<string, mixed>
     */
    protected function paged(array $where, array $order): array
    {
        $cell    = $this->sortedCell();
        $perPage = $this->perPage();

        return [
            ...$this->filtered($where),
            'ORDER' => $cell === null ? $order : $this->orderBy($cell, strtoupper($this->sorting()->order), $order),
            ...($perPage > 0 ? ['LIMIT' => [($this->page() - 1) * $perPage, $perPage]] : []),
        ];
    }

    /**
     * Get the order of a query of a Table\Queried table sorted by a column: the column of Cell::$sortBy, then
     * the default order for the rows equal by it. A table overrides it to sort by an expression, returning
     * `Db::raw()` with the whole order, e.g. `COALESCE(NULLIF(<showname>, ''), <login>) ASC, <id> ASC`.
     *
     * @param Cell                  $cell      Sorted column.
     * @param string                $direction `ASC` or `DESC`.
     * @param array<string, string> $order     Default order of paged().
     * @return array<string, string>|Raw
     */
    protected function orderBy(Cell $cell, string $direction, array $order): array|Raw
    {
        return [$cell->sortBy => $direction] + $order;
    }

    /**
     * Get the condition of a filter in a query of a Table\Queried table: the column of the field, its `column`
     * in filters() or the field, is one of the values or none of them. A table overrides it for a field that is
     * not a column, e.g. a value in a JSON list.
     *
     * @param string   $field    Key of the field in filters().
     * @param string   $operator `is` or `not`.
     * @param string[] $values   Known values of the field.
     * @return array<string, mixed>
     */
    protected function filterWhere(string $field, string $operator, array $values): array
    {
        $column = ($this->fields()[$field] ?? null)?->column ?: $field;

        return [$column . ($operator === 'not' ? '[!]' : '') => $values];
    }

    /**
     * Get the fields of filters(), read once.
     *
     * @return array<string, Filter>
     */
    private function fields(): array
    {
        return $this->fields ??= $this->filters();
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
     * Get the rows that match the filters in the order of sorting(), data() runs on the first call.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        if ($this->rows === null) {
            $filters    = $this->activeFilters();
            $rows       = $filters ? array_values(array_filter($this->data(), fn (array $row) => $this->matches($row, $filters))) : $this->data();
            $this->rows = $this->sort($rows);
        }

        return $this->rows;
    }

    /**
     * Get the sorting of the query: `orderby`, the key of a sortable column, and `order`, `asc` or `desc` by default;
     * without a known column the rows keep the order of data().
     *
     * @return Sorting
     */
    public function sorting(): Sorting
    {
        $query = $this->input ?? $_GET;
        $by    = is_string($query['orderby'] ?? null) ? $query['orderby'] : '';

        return new Sorting(
            by: array_any($this->cells, fn (Cell $cell) => $cell->sortable && $cell->key === $by) ? $by : '',
            order: ($query['order'] ?? '') === 'asc' ? 'asc' : 'desc',
        );
    }

    /**
     * Sort the rows by the values of sortValue() for the column of sorting(): numbers as numbers,
     * the rest as text. Equal rows keep their order.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function sort(array $rows): array
    {
        $cell  = $this->sortedCell();
        $order = $this->sorting()->order;
        if ($cell === null) {
            return $rows;
        }

        // every value is prepared once, not on each comparison
        $values = [];
        foreach ($rows as $index => $row) {
            $values[$index] = $this->sortValue($row, $cell);
        }

        $direction = $order === 'asc' ? 1 : -1;
        $indexes   = array_keys($rows);
        usort($indexes, function (int|string $a, int|string $b) use ($values, $direction): int {
            [$left, $right] = [$values[$a], $values[$b]];

            return $direction * (is_string($left) || is_string($right) ? strnatcmp((string) $left, (string) $right) : $left <=> $right);
        });

        return array_map(fn (int|string $index) => $rows[$index], $indexes);
    }

    /**
     * Get the value a row is sorted by in PHP: the value of Cell::$sortBy, a value object of Table\Values as its text;
     * a number stays a number, text is compared without case. A table overrides it to sort by something else,
     * e.g. a name made of several values of the row.
     *
     * @param array<string, mixed> $row
     * @param Cell                 $cell Sorted column.
     * @return int|float|string
     */
    protected function sortValue(array $row, Cell $cell): int|float|string
    {
        $value = $row[$cell->sortBy] ?? '';

        return is_int($value) || is_float($value) ? $value : mb_strtolower((string) $value);
    }

    /**
     * Get the column of sorting(), null without one.
     *
     * @return Cell|null
     */
    private function sortedCell(): ?Cell
    {
        $by = $this->sorting()->by;
        foreach ($this->cells as $cell) {
            if ($by !== '' && $cell->key === $by) {
                return $cell;
            }
        }

        return null;
    }

    /**
     * Describe the fields the rows can be filtered by, keyed by the row key. Without fields the table has no filter.
     *
     * @return array<string, Filter>
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
        $this->count  = null;

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
        foreach ($this->fields() as $field => $filter) {
            $param = $filter->param ?: $field;
            if (in_array($param, self::$reserved, true)) {
                throw new LogicException(sprintf('The filter "%s" of %s takes the reserved query parameter "%s".', $field, static::class, $param));
            }

            $options = array_map('strval', array_keys($filter->options));
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
     * Check a row against all filters in PHP, see matchesFilter().
     *
     * @param array<string, mixed> $row
     * @param array<int, array{field: string, operator: string, values: string[]}> $filters
     * @return bool
     */
    private function matches(array $row, array $filters): bool
    {
        foreach ($filters as ['field' => $field, 'operator' => $operator, 'values' => $values]) {
            if (! $this->matchesFilter($row, $field, $operator, $values)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check a row against a filter in PHP: the value of the field in the row, a scalar or a list, is one
     * of the values for `is` and none of them for `not`. A table overrides it for a field that is not a value
     * of the row, e.g. a date range or a part of a text.
     *
     * @param array<string, mixed> $row
     * @param string               $field    Key of the field in filters().
     * @param string               $operator `is` or `not`.
     * @param string[]             $values   Known values of the field.
     * @return bool
     */
    protected function matchesFilter(array $row, string $field, string $operator, array $values): bool
    {
        $value = $row[$field] ?? [];
        $found = array_intersect(array_map('strval', $value instanceof Stringable ? [$value] : (array) $value), $values) !== [];

        return $found !== ($operator === 'not');
    }

    /**
     * Describe the columns with cell().
     *
     * @return Cell[]
     */
    abstract public function cells(): array;

    /**
     * Get the variables of parts/table/table-header above the table: title, badge and the tools to show, e.g. `empty`,
     * the Values\Action of the button emptying the trash; `parts` are the Values\Part of the list for the select
     * of the filter by key, `part` is the key of the current one.
     *
     * @return array<string, mixed>
     */
    public function headData(): array
    {
        return [];
    }

    /**
     * Get the bulk actions of the selected rows, none by default.
     *
     * @return Bulk|null
     */
    public function bulk(): ?Bulk
    {
        return null;
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
