<?php

declare(strict_types=1);

namespace Expansa\Patterns;

use ArrayAccess;
use JsonSerializable;

/**
 * Attribute bag with array access and fluent setters: `$column->nullable()->default(0)`.
 *
 * @package Expansa\Patterns
 */
class Fluent implements ArrayAccess, JsonSerializable
{
    public function __construct(

        /**
         * Attribute values by key.
         */
        public protected(set) array $attributes = [],
    ) {} // phpcs:ignore

    /**
     * Get an attribute value by key.
     *
     * @param int|string $key     The attribute key.
     * @param null|mixed $default Default value if the key does not exist.
     * @return mixed The attribute value or the default value.
     */
    public function get(int|string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : $default;
    }

    /**
     * Convert the instance to an array.
     *
     * @return array The attributes as an array.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Serialize the instance to a value that can be serialized to JSON.
     *
     * @return array The attributes array.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the instance to a JSON string.
     *
     * @param int $options JSON encoding options.
     * @return string The JSON-encoded attributes.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Check if an offset exists.
     *
     * @param mixed $offset The offset to check.
     * @return bool True if the offset exists, false otherwise.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Get the value at a given offset.
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed The value at the offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set the value at a given offset.
     *
     * @param mixed $offset The offset to set.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Unset the value at a given offset.
     *
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Get an attribute dynamically.
     *
     * @param string $key The attribute key.
     * @return mixed The attribute value.
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Set an attribute dynamically.
     *
     * @param string $key   The attribute key.
     * @param mixed  $value The attribute value.
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Check if an attribute is set dynamically.
     *
     * @param string $key The attribute key.
     * @return bool True if the attribute is set, false otherwise.
     */
    public function __isset(string $key): bool
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute dynamically.
     *
     * @param string $key The attribute key.
     * @return void
     */
    public function __unset(string $key): void
    {
        $this->offsetUnset($key);
    }

    /**
     * Handle dynamic method calls to set attributes.
     *
     * @param string $method     The method name.
     * @param array  $parameters The method parameters.
     * @return static
     */
    public function __call(string $method, array $parameters): static
    {
        $this->attributes[$method] = count($parameters) > 0 ? reset($parameters) : true;

        return $this;
    }
}
