<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

/**
 * Sorting of a table, see Table::sorting(): the column and the direction.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Sorting
{
    public function __construct(

        /**
         * Key of the sorted column, empty for the order of Table::data().
         */
        public string $by = '',

        /**
         * Direction, `asc` or `desc`.
         */
        public string $order = 'desc',
    ) {}
}
