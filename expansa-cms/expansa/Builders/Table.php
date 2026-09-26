<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Expansa\Builders\Table\Abstracts\TableBase;
use Expansa\Builders\Table\Cell;
use Expansa\Facades\Safe;

/**
 * Class Table.
 *
 * Base class for displaying a list of items in HTML table.
 *
 * @package Dashboard\Tables
 */
abstract class Table extends TableBase
{
    /**
     * File that enqueues the items filter form, included by every table.
     */
    private static string $filter = '';

    public static function configure(string $filter): void
    {
        self::$filter = $filter;
    }

    public function __construct(

        /**
         * Data for rendering the table.
         */
        public array $data = [],

        /**
         * Table cells list.
         */
        public array $cells = [],
    )
    {
        if (self::$filter !== '') {
            require_once self::$filter;
        }

        $this->data  = $this->data();
        $this->cells = $this->cells();
    }

    public function cell(string $key): Cell
    {
        return new Cell($key);
    }

    /**
     * Calculate grid CSS styles.
     *
     * @param array $columns
     * @return string
     */
    public function stylize(array $columns): string
    {
        $repeat = 1;
        $styles = [];
        if ($columns) {
            foreach ($columns as $i => $column) {
                $width    = Safe::trim($column->width ?: '1fr');
                $flexible = Safe::bool($column->flexible ?? false);
                if ($flexible) {
                    $width = sprintf('minmax(%s, 1fr)', $width);
                }

                if ($width) {
                    if ($width === ( $styles[ $i - 1 ] ?? null )) {
                        $repeat++;
                        $styles[ $i - 1 ] = sprintf('repeat(%s, %s)', $repeat, $width);
                    } else {
                        $repeat = 1;
                        $styles[ $i ] = $width;
                    }
                }
            }
        }

        if ($styles) {
            return sprintf('--expansa-grid-template-columns: %s', implode(' ', $styles));
        }

        return '';
    }
}
