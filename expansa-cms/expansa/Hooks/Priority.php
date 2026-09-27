<?php

declare(strict_types=1);

namespace Expansa\Hooks;

/**
 * Common listener priorities; listeners run in ascending order and any integer is valid.
 *
 * @package Expansa\Hooks
 */
final class Priority
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
}
