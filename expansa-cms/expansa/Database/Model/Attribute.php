<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use Closure;

/**
 * A get/set mutator pair for one model attribute - see {@see \Expansa\Database\Model\HasAttributes}.
 * Typed `?Closure`, not `?callable`: every real usage across the app passes an `fn()`/`function()`
 * literal, so the narrower type catches a typo (e.g. a bare method name) at the call site instead
 * of a confusing failure deep inside call_user_func().
 */
class Attribute
{
    public function __construct(

        /**
         * Runs on read: (rawValue, allAttributes) -> exposed value. Null skips mutation.
         */
        public ?Closure $get = null,

        /**
         * Runs on write: (newValue, allAttributes) -> value actually stored. Null skips mutation.
         */
        public ?Closure $set = null,
    ) {} // phpcs:ignore

    /**
     * Build an attribute with both a getter and setter (either may be omitted).
     *
     * @param ?Closure $get Mutator run on read.
     * @param ?Closure $set Mutator run on write.
     * @return static
     */
    public static function make(?Closure $get = null, ?Closure $set = null): static
    {
        return new static($get, $set);
    }

    /**
     * Build a read-only attribute (no set mutator).
     *
     * @param Closure $get Mutator run on read.
     * @return static
     */
    public static function get(Closure $get): static
    {
        return new static(get: $get);
    }

    /**
     * Build a write-only attribute (no get mutator - reads return the raw stored value).
     *
     * @param Closure $set Mutator run on write.
     * @return static
     */
    public static function set(Closure $set): static
    {
        return new static(set: $set);
    }
}
