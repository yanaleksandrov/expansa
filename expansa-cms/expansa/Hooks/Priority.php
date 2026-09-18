<?php

declare(strict_types=1);

namespace Expansa\Hooks;

/**
 * Well-known priority values for hook listeners.
 *
 * Listeners run in ascending priority order, so a lower value runs earlier. These constants are
 * only convenient defaults - any integer, including negative ones, is a valid priority.
 */
class Priority
{
    /**
     * Runs before listeners registered with the default priority.
     *
     * @var int
     */
    public const int HIGH = 100;

    /**
     * Default priority used when none is specified.
     *
     * @var int
     */
    public const int BASE = 200;

    /**
     * Runs after listeners registered with the default priority.
     *
     * @var int
     */
    public const int LOW = 300;
}
