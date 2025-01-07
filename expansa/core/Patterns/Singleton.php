<?php

declare(strict_types=1);

namespace Expansa\Patterns;

/**
 * Singleton is an evolution of the Singleton pattern, where instead of a single instance
 * you can create and use many. From a practical point of view, a singleton can be used
 * to create single-type classes that should not overlap each other. The simplest example
 * is working with multiple configurations, where each singleton class stores some different
 * set of data.
 */
trait Singleton
{
    /**
     * A real instance of the class is inside a static field. In this case the static field
     * is an array, where each instance of the class is accessible by a flag.
     */
    protected static array $instances = [];

    /**
     * This is a static method that controls access to a Singleton.
     * On the first run, it creates a Singleton instance and places it in a static field.
     * On subsequent runs, it returns to the client the object stored in the static field.
     */
    public static function init(...$args)
    {
        $id = static::class;
        if (!isset(self::$instances[$id])) {
            self::$instances[$id] = new self(...$args);
        }
        return self::$instances[$id];
    }

    /**
     * The constructor of a Singleton should not be public, but should be hidden to prevent
     * the creation of an object through the `new` operator. However, it cannot be private
     * if we want to allow the creation of subclasses.
     */
    protected function __construct(...$args) {} // phpcs:ignore

    /**
     * Cloning and deserialization are not allowed.
     */
    protected function __clone() {} // phpcs:ignore

    /**
     * Singleton should not be recoverable from strings.
     */
    public function __wakeup() {} // phpcs:ignore
}
