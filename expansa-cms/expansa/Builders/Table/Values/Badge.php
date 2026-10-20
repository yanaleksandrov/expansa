<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `badge` cell: a short text in a badge with an icon before it, a dot by default.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Badge implements Stringable
{
    public function __construct(

        /**
         * Text of the badge, nothing is shown for an empty one.
         */
        public string $text,

        /**
         * Classes of the badge.
         */
        public string $class = 'badge',

        /**
         * Classes of the icon before the text.
         */
        public string $icon = 'badge badge--dark',

        /**
         * HTML attributes of the badge, e.g. of Table::action().
         *
         * @var array<string, mixed>
         */
        public array $attributes = [],
    ) {}

    /**
     * Get the text the rows are sorted and filtered by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->text;
    }
}
