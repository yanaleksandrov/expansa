<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `code` cell: a slug, an identifier or a key in monospace, with the copy button.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Code implements Stringable
{
    public function __construct(

        /**
         * Text of the code.
         */
        public string $text,

        /**
         * Whether to show the button copying the text.
         */
        public bool $copy = true,
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
