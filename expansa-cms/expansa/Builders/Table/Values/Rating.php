<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `rating` cell: stars out of five with the number of reviews, see parts/rating.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Rating implements Stringable
{
    public function __construct(

        /**
         * Rating from 0 to 5.
         */
        public float $value,

        /**
         * Number of reviews.
         */
        public int $reviews = 0,
    ) {}

    /**
     * Get the rating the rows are sorted by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
