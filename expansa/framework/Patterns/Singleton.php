<?php

declare(strict_types=1);

namespace Expansa\Patterns;

use Expansa\Patterns\Exception\SingletonException;

/**
 * This trait implements the Singleton pattern, ensuring that a class has only one instance
 * and provides a global point of access to it. The instance is stored in a static array,
 * allowing for multiple Singleton instances identified by a class name.
 */
trait Singleton
{
    /**
     * This is a static method that controls access to a Singleton.
     * On the first run, it creates a Singleton instance and places it in a static field.
     * On subsequent runs, it returns to the client the object stored in the static field.
     */
    public static function init(...$args)
    {
        static $instances;

        $id = static::class;
        if (!isset($instances[$id])) {
            $instances[$id] = new self(...$args);
        }
        return $instances[$id];
    }

    /**
     * The constructor of a Singleton should not be public, but should be hidden to prevent
     * the creation of an object through the `new` operator. However, it cannot be private
     * if we want to allow the creation of subclasses.
     *
     * @param mixed ...$args Optional arguments for the class constructor.
     */
    protected function __construct(...$args) {} // phpcs:ignore

    /**
     * Prevents cloning of the instance.
     * Cloning is not allowed to ensure the Singleton pattern is maintained.
     *
     * @throws SingletonException
     */
    protected function __clone()
    {
        throw new SingletonException('You can not clone a singleton.');
    }

    /**
     * Prevents deserialization of the instance.
     * The Singleton instance should not be recoverable from strings to maintain its integrity.
     *
     * @throws SingletonException
     */
    public function __wakeup()
    {
        throw new SingletonException('You can not deserialize a singleton.');
    }

    /**
     * Prevents serialization of the instance.
     *
     * @throws SingletonException Thrown when attempting to serialize a Singleton instance.
     */
    public function __sleep()
    {
        throw new SingletonException('You can not serialize a singleton.');
    }
}
