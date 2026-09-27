<?php

declare(strict_types=1);

namespace Expansa\Builders\Table;

/**
 * Base of a dashboard table: a subclass returns the rows and describes the columns.
 *
 * @package Expansa\Builders\Table
 */
abstract class AbstractTable
{
    /**
     * File that enqueues the items filter form, included by every table.
     */
    private static string $filter = '';

    /**
     * Rows to render, from data().
     */
    public readonly array $data;

    /**
     * Columns, from cells().
     *
     * @var Cell[]
     */
    public readonly array $cells;

    /**
     * Set the file included before every table.
     *
     * @param string $filter File that enqueues the items filter form.
     * @return void
     */
    public static function configure(string $filter): void
    {
        self::$filter = $filter;
    }

    public function __construct()
    {
        if (self::$filter !== '') {
            require_once self::$filter;
        }

        $this->data  = $this->data();
        $this->cells = $this->cells();
    }

    /**
     * Get the rows of the table.
     *
     * @return array
     */
    abstract public function data(): array;

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
        return new Cell($key);
    }

    /**
     * Get the `--expansa-grid-template-columns` style from the column widths, repeated widths merged.
     *
     * @param Cell[] $columns
     * @return string Empty without columns.
     */
    public function stylize(array $columns): string
    {
        $repeat = 1;
        $styles = [];
        foreach ($columns as $i => $column) {
            $width = trim($column->width ?: '1fr');
            if ($column->flexible) {
                $width = sprintf('minmax(%s, 1fr)', $width);
            }

            if ($width === ($styles[$i - 1] ?? null)) {
                $repeat++;
                $styles[$i - 1] = sprintf('repeat(%s, %s)', $repeat, $width);
            } else {
                $repeat     = 1;
                $styles[$i] = $width;
            }
        }

        return $styles ? sprintf('--expansa-grid-template-columns: %s', implode(' ', $styles)) : '';
    }
}
