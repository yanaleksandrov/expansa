<?php

declare(strict_types=1);

namespace Expansa\Patterns;

/**
 * Static access to one shared instance of a class, created on the first call.
 * Facades pointing to the same class share its instance.
 *
 * @package Expansa\Patterns
 */
abstract class Facade
{
    /**
     * Created instances by class name.
     *
     * @var array<string, object>
     */
    private static array $instances = [];

    /**
     * Pass a static call to the instance.
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        // inlined resolve(): this runs on every facade call
        $class = static::getStaticClassAccessor();

        return (self::$instances[$class] ??= new $class(...static::getConstructorArgs()))->{$method}(...$args);
    }

    /**
     * Get the instance, creating it on the first call.
     *
     * @return object
     */
    protected static function resolve(): object
    {
        $class = static::getStaticClassAccessor();

        return self::$instances[$class] ??= new $class(...static::getConstructorArgs());
    }

    /**
     * Get the class of the instance.
     *
     * @return class-string
     */
    abstract protected static function getStaticClassAccessor(): string;

    /**
     * Get the constructor arguments of the instance.
     *
     * @return array
     */
    protected static function getConstructorArgs(): array
    {
        return [];
    }

    /**
     * Forget the instance of this facade, or of every facade when called on Facade itself,
     * so the next call creates a new one, e.g. between tests.
     *
     * @return void
     */
    public static function forgetResolved(): void
    {
        if (static::class === self::class) {
            self::$instances = [];
            return;
        }

        unset(self::$instances[static::getStaticClassAccessor()]);
    }

    /**
     * Use the given instance behind this facade, e.g. a test double.
     *
     * @param object $instance
     * @return void
     */
    public static function swap(object $instance): void
    {
        self::$instances[static::getStaticClassAccessor()] = $instance;
    }
}
