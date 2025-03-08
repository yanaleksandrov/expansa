<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Expansa\Builders\Table\Abstracts\TableBase;
use Expansa\Facades\Safe;
use Expansa\Facades\View;
use Expansa\Support\Arr;
use Expansa\Builders\Table\Cell;
use Expansa\Builders\Table\Row;

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
     * Table constructor.
     *
     * @param array $data Data for rendering the table.
     * @param array $cells Table cells list.
     */
    public function __construct(
        public array $data = [],
        public array $cells = []
    )
    {
        // include filter
        require_once EX_DASHBOARD . 'forms/items-filter.php';

        $this->data  = $this->data();
        $this->cells = $this->cells();
    }

    public function cell(string $key): Cell
    {
        return new Cell($key);
    }

    /**
     * Calculate grid css styles.
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
