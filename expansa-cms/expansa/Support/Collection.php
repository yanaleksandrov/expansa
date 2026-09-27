<?php

declare(strict_types=1);

namespace Expansa\Support;

/**
 * Iteration helpers for large data sets.
 *
 * @package Expansa\Support
 */
final class Collection
{
    /**
     * Pass every item returned by a data callback to a callback, through a generator.
     * Slower than foreach, for large data sets only.
     *
     * @param callable $dataCallback Returns the items.
     * @param callable $callback     Gets an item and its index.
     * @return void
     */
    public static function each(callable $dataCallback, callable $callback): void
    {
        $generator = (function () use ($dataCallback) {
            $items = $dataCallback();
            if (is_array($items)) {
                foreach ($items as $item) {
                    yield $item;
                }
            }
        })();

        foreach ($generator as $index => $item) {
            $callback($item, $index);
        }
    }
}
