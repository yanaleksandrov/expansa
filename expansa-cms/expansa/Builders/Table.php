<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Expansa\Builders\Table\Cell;

/**
 * Base of a dashboard table: a subclass returns the rows in data() and describes the columns
 * in cells() with cell(). The template renders a row cell by cell, each with the view of its column.
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
     * Default view of the cells, `->view('date')` of a column adds `-date` to it.
     */
    private static string $cellView = '';

    /**
     * Rows from data(), null until getData() is called.
     */
    private ?array $rows = null;

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
     * @param string $cellView View name, e.g. `components/table/cell`.
     * @return void
     */
    public static function configure(string $filter = '', string $cellView = ''): void
    {
        self::$filter   = $filter;
        self::$cellView = $cellView;
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
     * @return array
     */
    abstract public function data(): array;

    /**
     * Get the rows to render: data() runs on the first call, so creating a table runs no queries.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->rows ??= $this->data();
    }

    /**
     * Describe the columns with cell().
     *
     * @return Cell[]
     */
    abstract public function cells(): array;

    /**
     * Start a column description.
     *
     * @param string $key Key of the value in a row.
     * @return Cell
     */
    public function cell(string $key): Cell
    {
        return new Cell($key, view: self::$cellView);
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
