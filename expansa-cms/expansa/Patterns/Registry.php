<?php

declare(strict_types=1);

namespace Expansa\Patterns;

/**
 * The Registry pattern is designed primarily to solve the global scope problem.
 * This is a very common problem when you want to share data,
 * but it is not desirable to clog the global scope.
 *
 * 1. The example shows how to get access to string value by key:
 *
 * ```php
 * Registry::set( 'key', 'value' );
 * print_r( Registry::get( 'key' ) );
 * output: 'value'
 * ```
 *
 * 2. As a key you can specify point-separated values. In this case, the class will try to find the value in the array:
 *
 * ```php
 * Registry::set( 'attributes', [ 'textarea' => [ 'value', 'row' ] ] );
 * print_r( Registry::get( 'attributes.textarea' ) );
 * output: array( 'value', 'row' )
 * ```
 */
class Registry
{
    private static array $registry = [];

    /**
     * Resolvers of lazy items, keyed by top-level key.
     *
     * @var array<string, callable>
     */
    private static array $lazy = [];

    /**
     * Put item into the registry.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        if (! isset(self::$registry[ $key ])) {
            self::$registry[ $key ] = $value;

            unset(self::$lazy[ $key ]);
        }
    }

    /**
     * Put item into the registry on first get(): $resolver returns its value, e.g. by requiring a data file.
     *
     * @param  string   $key
     * @param  callable $resolver
     * @return void
     */
    public static function lazy(string $key, callable $resolver): void
    {
        if (! isset(self::$registry[ $key ])) {
            self::$lazy[ $key ] = $resolver;
        }
    }

    /**
     * Get item by key.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return null|mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $root = strstr($key, '.', true) ?: $key;
        if (isset(self::$lazy[ $root ])) {
            self::set($root, (self::$lazy[ $root ])());
        }

        $array = self::$registry;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[ $segment ];
        }

        return $array;
    }
}
