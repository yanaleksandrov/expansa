<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `progress` cell: the bar of the percentage with the label and the percentage above it.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Progress implements Stringable
{
    public function __construct(

        /**
         * Percentage from 0 to 100.
         */
        public int $percent,

        /**
         * Text before the percentage, e.g. "96 of 128".
         */
        public string $label = '',

        /**
         * Classes of the bar, e.g. a color: `bg-green`.
         */
        public string $class = '',
    ) {}

    /**
     * Get the percentage the rows are sorted by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->percent;
    }
}
