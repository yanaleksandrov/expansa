<?php

declare(strict_types=1);

namespace Expansa\Hooks\Attributes;

use Attribute;

/**
 * Priority of a listener method, wins over the priority passed to add().
 *
 * @package Expansa\Hooks
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Priority
{
    public function __construct(

        /**
         * Ascending: a lower value runs earlier, see the Expansa\Hooks\Priority constants.
         */
        public int $priority,
    ) {}
}
