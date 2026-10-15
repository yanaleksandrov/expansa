<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Expansa\Builders\Table\Cell;

/**
 * Base of a dashboard table: a subclass returns the rows in data() and describes the columns
 * in cells() with cell(). The template renders a row cell by cell, each with the view of its column,
 * see Table\Cell for the variables of the view.
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
     * Rows from data(), null until getData() is called.
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
     * Raw `filter` input set by filterBy(), null reads the query parameter.
     */
    private ?array $input = null;

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
     * Set the file included before every table and the default view of the cells.
     *
     * @param string $filter   File that enqueues the items filter form.
     * @param string $cellView Prefix of the cell views, e.g. `parts/table/cell`.
     * @return void
     */
    public static function configure(string $filter = '', string $cellView = ''): void
    {
        self::$filter = $filter;

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
     * Get the rows to render: data() runs on the first call, so creating a table runs no queries.
     * Rows that do not match the active filters are left out.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getData(): array
    {
        if ($this->rows === null) {
            $filters    = $this->activeFilters();
            $this->rows = $filters ? array_values(array_filter($this->data(), fn (array $row) => self::matches($row, $filters))) : $this->data();
        }

        return $this->rows;
    }

    /**
     * Describe the fields the rows can be filtered by, keyed by the row key:
     * `label`, `icon` and `options` as value => label. Without fields the table has no filter.
     *
     * @return array<string, array{label: string, icon?: string, options: array<string|int, string>}>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Filter the rows by the given input instead of the `filter` query parameter, e.g. of an API request.
     *
     * @param array $filter Items with `field`, `operator` and `values`.
     * @return static
     */
    public function filterBy(array $filter): static
    {
        $this->input  = $filter;
        $this->active = null;
        $this->rows   = null;

        return $this;
    }

    /**
     * Get the filters of the `filter` query parameter, each a field of filters(), the operator
     * `is` or `not` and the known values of the field; a filter without values is skipped.
     *
     * @return array<int, array{field: string, operator: string, values: string[]}>
     */
    public function activeFilters(): array
    {
        if ($this->active !== null) {
            return $this->active;
        }

        $fields = $this->filters();
        $query  = $this->input ?? (is_array($_GET['filter'] ?? null) ? $_GET['filter'] : []);

        $this->active = [];
        foreach ($query as $filter) {
            $field   = is_array($filter) ? (string) ($filter['field'] ?? '') : '';
            $options = $fields[$field]['options'] ?? [];
            $values  = array_values(array_intersect(array_map('strval', (array) ($filter['values'] ?? [])), array_map('strval', array_keys($options))));
            if ($values === []) {
                continue;
            }

            $this->active[] = [
                'field'    => $field,
                'operator' => ($filter['operator'] ?? '') === 'not' ? 'not' : 'is',
                'values'   => $values,
            ];
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
     * Get the data of the header above the table: title, badge and the tools to show.
     *
     * @return array<string, mixed>
     */
    public function headData(): array
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
