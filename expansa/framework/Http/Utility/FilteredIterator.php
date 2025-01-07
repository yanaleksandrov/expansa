<?php

namespace Expansa\Http\Utility;

use ArrayIterator;
use ReturnTypeWillChange;

/**
 * Iterator for arrays requiring filtered values
 *
 * @package Expansa\Http
 */
final class FilteredIterator extends ArrayIterator
{
    /**
     * Callback to run as a filter
     *
     * @var callable
     */
    private $callback;

    /**
     * Create a new iterator
     *
     * @param array    $data     The array or object to be iterated on.
     * @param callable $callback Callback to be called on each value
     */
    public function __construct(array $data, callable $callback)
    {
        parent::__construct($data);

        $this->callback = $callback;
    }

    /**
     * Prevent unserialization of the object for security reasons.
     *
     * @param array $data Restored array of data originally serialized.
     * @return void
     */
    #[ReturnTypeWillChange]
    public function __unserialize(array $data): void {} // phpcs:ignore

    /**
     * Get the current item's value after filtering
     *
     * @return string
     */
    #[ReturnTypeWillChange]
    public function current(): string
    {
        $value = parent::current();

        if (is_callable($this->callback)) {
            $value = call_user_func($this->callback, $value);
        }

        return $value;
    }
}
