<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `version` cell: the installed version and, with an update, the arrow to the latest one
 * and the badge of the kind of the update, e.g. "Major".
 *
 * @package Expansa\Builders\Table
 */
final readonly class Version implements Stringable
{
    public function __construct(

        /**
         * Installed version.
         */
        public string $current,

        /**
         * Latest version, empty when the installed one is the latest.
         */
        public string $latest = '',

        /**
         * Badge of the kind of the update.
         */
        public ?Badge $kind = null,
    ) {}

    /**
     * Get the version the rows are sorted and filtered by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->current;
    }
}
