<?php

namespace Expansa\Http\Response;

use ArrayIterator;
use Expansa\Http\HttpException;
use Expansa\Http\Exception\InvalidArgument;
use Expansa\Http\Utility\CaseInsensitiveDictionary;
use Expansa\Http\Utility\FilteredIterator;

/**
 * Case-insensitive dictionary, suitable for HTTP headers
 *
 * @package Expansa\Http
 */
class Headers extends CaseInsensitiveDictionary
{
    /**
     * Get the given header
     *
     * Unlike {@see Headers::getValues()}, this returns a string. If there are
     * multiple values, it concatenates them with a comma as per RFC2616.
     *
     * Avoid using this where commas may be used unquoted in values, such as
     * Set-Cookie headers.
     *
     * @param string $offset Name of the header to retrieve.
     * @return string|null Header value
     */
    public function offsetGet($offset): ?string
    {
        if (is_string($offset)) {
            $offset = strtolower($offset);
        }

        if (!isset($this->data[$offset])) {
            return null;
        }

        return $this->flatten($this->data[$offset]);
    }

    /**
     * Set the given item
     *
     * @param string $offset Item name
     * @param string $value  Item value
     * @throws HttpException On attempting to use dictionary as list (`invalidset`)
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            throw new HttpException('Object is a dictionary, not a list', 'invalidset');
        }

        if (is_string($offset)) {
            $offset = strtolower($offset);
        }

        if (!isset($this->data[$offset])) {
            $this->data[$offset] = [];
        }

        $this->data[$offset][] = $value;
    }

    /**
     * Get all values for a given header
     *
     * @param string $offset Name of the header to retrieve.
     * @return array|null Header values
     * @throws InvalidArgument When the passed argument is not valid as an array key.
     */
    public function getValues(string $offset): ?array
    {
        return $this->data[strtolower($offset)] ?? null;
    }

    /**
     * Flattens a value into a string
     *
     * Converts an array into a string by imploding values with a comma, as per
     * RFC2616's rules for folding headers.
     *
     * @param string|array $value Value to flatten
     * @return string Flattened value
     *
     * @throws InvalidArgument When the passed argument is not a string or an array.
     */
    public function flatten($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return implode(',', $value);
        }

        throw InvalidArgument::create(1, '$value', 'string|array', gettype($value));
    }

    /**
     * Get an iterator for the data
     *
     * Converts the internally stored values to a comma-separated string if there is more
     * than one value for a key.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new FilteredIterator($this->data, [$this, 'flatten']);
    }
}
