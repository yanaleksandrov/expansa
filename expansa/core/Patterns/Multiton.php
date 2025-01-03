<?php

declare(strict_types=1);

namespace Expansa\Patterns;

/**
 * Multiton is an evolution of the Singleton pattern, where
 * instead of a single instance you can create and use many.
 * From a practical point of view, a multiton can be used
 * to create single-type classes that should not overlap each other.
 * The simplest example is working with multiple configurations,
 * where each multitone class stores some different set of data.
 *
 * @since 2025.1
 */
trait Multiton
{
    /**
     * A real instance of the class is inside a static field.
     * In this case the static field is an array, where
     * each instance of the class is accessible by a flag.
     *
     * @since 2025.1
     */
    protected static array $instances = [];

    /**
     * This is a static method that controls access to a singleton Multiton.
     * On the first run, it creates a Multiton instance and places it in a static field.
     * On subsequent runs, it returns to the client the object stored in the static field.
     *
     * @since 2025.1
     */
    public static function init(string $id, ...$args)
    {
        if (! isset(self::$instances[ $id ])) {
            self::$instances[ $id ] = new self(...$args);
        }
        return self::$instances[ $id ];
    }

    /**
     * The Multiton constructor should not be public, but should be hidden
     * to prevent the object from being created via the new operator.
     *
     * @since 2025.1
     */
    protected function __construct(...$args) {} // phpcs:ignore

    /**
     * Cloning and deserialization are not allowed.
     *
     * @since 2025.1
     */
    protected function __clone() {} // phpcs:ignore

    /**
     * Multiton should not be recoverable from strings.
     *
     * @since 2025.1
     */
    public function __wakeup() {} // phpcs:ignore
}
