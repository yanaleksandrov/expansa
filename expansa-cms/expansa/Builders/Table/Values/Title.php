<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `title` cell: the title of an item linking to its page, with quick actions below shown on hover.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Title implements Stringable
{
    public function __construct(

        /**
         * Title of the item.
         */
        public string $text,

        /**
         * URL of the page of the item, `#` for none.
         */
        public string $url = '#',

        /**
         * Quick actions of the row.
         *
         * @var Link[]
         */
        public array $actions = [],
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
