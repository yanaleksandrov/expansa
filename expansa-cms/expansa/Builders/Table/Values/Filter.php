<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

/**
 * Field the rows of a table can be filtered by, see Table::filters(); its public properties
 * also go to the `table` component of youla-expansa.js as JSON.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Filter
{
    public function __construct(

        /**
         * Label of the field.
         */
        public string $label,

        /**
         * Values of the field: labels by value.
         *
         * @var array<string|int, string>
         */
        public array $options,

        /**
         * Classes of the icon.
         */
        public string $icon = '',

        /**
         * Query parameter of the field, the key of the field by default.
         */
        public string $param = '',

        /**
         * Column of the query of a Table\Queried table, the key of the field by default.
         */
        public string $column = '',
    ) {}
}
