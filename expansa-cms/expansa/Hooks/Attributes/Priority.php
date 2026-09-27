<?php

declare(strict_types=1);

namespace Expansa\Hooks\Attributes;

use Attribute;

/**
 * Priority of a listener method, wins over the priority passed to add().
 * Its constants are common priorities for add() as well; listeners run in ascending order and any integer is valid.
 *
 * @package Expansa\Hooks
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Priority
{
    /**
     * Runs before the default priority.
     */
    public const int HIGH = 100;

    /**
     * Default priority.
     */
    public const int BASE = 200;

    /**
     * Runs after the default priority.
     */
    public const int LOW = 300;

    public function __construct(

        /**
         * Ascending: a lower value runs earlier.
         */
        public int $priority,
    ) {}
}
