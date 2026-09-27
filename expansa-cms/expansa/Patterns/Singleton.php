<?php

declare(strict_types=1);

namespace Expansa\Patterns;

use LogicException;

/**
 * This trait implements the Singleton pattern, ensuring that a class has only one instance
 * and provides a global point of access to it. The instance is stored in a static array,
 * allowing for multiple Singleton instances identified by a class name.
 */
trait Singleton
{
    /**
     * Get the instance of the class, creating it with the arguments on the first call.
     *
     * @param mixed ...$args Constructor arguments, ignored after the first call.
     * @return self
     */
    public static function init(mixed ...$args): self
    {
        static $instances = [];

        return $instances[static::class] ??= new self(...$args);
    }

    /**
     * The constructor of a Singleton should not be public, but should be hidden to prevent
     * the creation of an object through the `new` operator. However, it cannot be private
     * if we want to allow the creation of subclasses.
     *
     * @param mixed ...$args Optional arguments for the class constructor.
     */
    protected function __construct(mixed ...$args) {} // phpcs:ignore

    /**
     * Prevents cloning of the instance.
     * Cloning is not allowed to ensure the Singleton pattern is maintained.
     *
     * @throws LogicException
     */
    protected function __clone()
    {
        throw new LogicException('You can not clone a singleton.');
    }

    /**
     * Prevents deserialization of the instance.
     * The Singleton instance should not be recoverable from strings to maintain its integrity.
     *
     * @throws LogicException
     */
    public function __wakeup()
    {
        throw new LogicException('You can not deserialize a singleton.');
    }

    /**
     * Prevents serialization of the instance.
     *
     * @throws LogicException Thrown when attempting to serialize a Singleton instance.
     */
    public function __sleep()
    {
        throw new LogicException('You can not serialize a singleton.');
    }
}