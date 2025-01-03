<?php

declare(strict_types=1);

namespace Expansa\Routing;

abstract class Router
{
    /**
     * Holds the single instance of the class.
     *
     * @var Handler|null
     *
     * @since 2025.1
     */
    protected static ?Handler $router = null;

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @since 2025.1
     */
    private function __construct() {} // phpcs:ignore

    /**
     * Prevents cloning of the instance.
     *
     * @since 2025.1
     */
    private function __clone() {} // phpcs:ignore

    /**
     * Returns the single instance of the class.
     * If the instance does not exist, it is created.
     *
     * @return Handler The singleton instance.
     *
     * @since 2025.1
     */
    protected static function router(): Handler
    {
        return self::$router ??= new Handler();
    }
}
