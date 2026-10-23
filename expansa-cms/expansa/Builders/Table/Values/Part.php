<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

/**
 * Part of the list in the select of the filter of a table, e.g. the trash, see Table::headData().
 *
 * @package Expansa\Builders\Table
 */
final readonly class Part
{
    public function __construct(

        /**
         * Label with the number of the rows.
         */
        public string $label,

        /**
         * Query parameters that open the part, the parameters of the other parts are dropped.
         *
         * @var array<string, string>
         */
        public array $query = [],
    ) {}
}
