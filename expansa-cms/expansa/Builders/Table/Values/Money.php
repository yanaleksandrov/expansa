<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `money` cell: an amount formatted in its currency, colored by the status, e.g. of the payment.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Money implements Stringable
{
    public function __construct(

        /**
         * Amount the rows are sorted by.
         */
        public float $amount,

        /**
         * Amount formatted with the currency, e.g. "$550.45".
         */
        public string $text,

        /**
         * Classes of the amount, e.g. `t-green` of a paid one.
         */
        public string $class = '',
    ) {}

    /**
     * Get the amount the rows are sorted by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->amount;
    }
}
