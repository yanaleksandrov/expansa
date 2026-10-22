<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `date` cell: a formatted date in `<time>`, with a note above it, e.g. the status of the post,
 * and the full date in the tooltip.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Date implements Stringable
{
    public function __construct(

        /**
         * Formatted date, or a text without one, e.g. "Never".
         */
        public string $text,

        /**
         * Machine-readable date of `<time datetime>`, e.g. `2026-10-22 14:05:00`; empty for a text without a date.
         */
        public string $datetime = '',

        /**
         * Full date in the tooltip.
         */
        public string $title = '',

        /**
         * Muted text above the date.
         */
        public string $note = '',
    ) {}

    /**
     * Get the date the rows are sorted and filtered by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->datetime;
    }
}
