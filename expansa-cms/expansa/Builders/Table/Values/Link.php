<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Link in a cell: an author of an extension, an item of a `links` cell, a quick action of a row;
 * without a URL the title is plain text.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Link implements Stringable
{
    public function __construct(

        /**
         * Text of the link.
         */
        public string $title,

        /**
         * URL of the link, empty for plain text.
         */
        public string $url = '',

        /**
         * Classes of the link.
         */
        public string $class = '',

        /**
         * HTML attributes of the link, e.g. of Table::action().
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
        return $this->title;
    }
}
