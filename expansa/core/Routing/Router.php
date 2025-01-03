<?php

declare(strict_types=1);

namespace Expansa\Routing;

abstract class Router
{
    /**
     * Holds the single instance of the class.
     *
     * @var Handler|null
     */
    protected static ?Handler $router = null;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {} // phpcs:ignore

    /**
     * Prevents cloning of the instance.
     */
    private function __clone() {} // phpcs:ignore

    /**
     * Returns the single instance of the class.
     * If the instance does not exist, it is created.
     *
     * @return Handler The singleton instance.
     */
    protected static function router(): Handler
    {
        return self::$router ??= new Handler();
    }
}
