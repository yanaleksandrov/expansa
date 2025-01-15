<?php

namespace Expansa\Http\Utility;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use ReturnTypeWillChange;
use Expansa\Http\Exception\HttpException;

/**
 * Case-insensitive dictionary, suitable for HTTP headers
 *
 * @package Expansa\Http
 */
class CaseInsensitiveDictionary implements ArrayAccess, IteratorAggregate
{
    /**
     * Creates a case-insensitive dictionary.
     *
     * @param array $data Dictionary/map to convert to case-insensitive
     * @throws HttpException
     */
    public function __construct(protected array $data = [])
    {
        foreach ($data as $offset => $value) {
            $this->offsetSet($offset, $value);
        }
    }

    /**
     * Check if the given item exists
     *
     * @param string $offset Item key
     * @return bool Does the item exist?
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        if (is_string($offset)) {
            $offset = strtolower($offset);
        }

        return isset($this->data[$offset]);
    }

    /**
     * Get the value for the item
     *
     * @param string $offset Item key
     * @return string|null Item value (null if the item key doesn't exist)
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset): ?string
    {
        if (is_string($offset)) {
            $offset = strtolower($offset);
        }

        if (!isset($this->data[$offset])) {
            return null;
        }

        return $this->data[$offset];
    }

    /**
     * Set the given item
     *
     * @param string $offset Item name
     * @param string $value  Item value
     * @throws HttpException On attempting to use dictionary as list (`invalidset`)
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            throw new HttpException('Object is a dictionary, not a list', 'invalidset');
        }

        if (is_string($offset)) {
            $offset = strtolower($offset);
        }

        $this->data[$offset] = $value;
    }

    /**
     * Unset the given header
     *
     * @param string $offset The key for the item to unset.
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        if (is_string($offset)) {
            $offset = strtolower($offset);
        }

        unset($this->data[$offset]);
    }

    /**
     * Get an iterator for the data
     *
     * @return ArrayIterator
     */
    #[ReturnTypeWillChange]
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Get the headers as an array
     *
     * @return array Header data
     */
    public function getAll(): array
    {
        return $this->data;
    }
}
