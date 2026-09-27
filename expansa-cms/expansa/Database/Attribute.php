<?php

declare(strict_types=1);

namespace Expansa\Database;

use Closure;

/**
 * Get/set mutators of one model attribute, returned by a model method named after the attribute:
 *
 *     protected function password(): Attribute
 *     {
 *         return new Attribute(set: fn (string $value) => password_hash($value, PASSWORD_DEFAULT));
 *     }
 *
 * @package Expansa\Database
 */
final class Attribute
{
    public function __construct(

        /**
         * Runs on read: (raw value, all attributes) -> exposed value, null reads the raw value.
         */
        public ?Closure $get = null,

        /**
         * Runs on write: (new value, all attributes) -> stored value, null stores it as is.
         */
        public ?Closure $set = null,
    ) {}

    /**
     * Create an attribute with only a read mutator.
     *
     * @param Closure $get
     * @return self
     */
    public static function get(Closure $get): self
    {
        return new self(get: $get);
    }

    /**
     * Create an attribute with only a write mutator.
     *
     * @param Closure $set
     * @return self
     */
    public static function set(Closure $set): self
    {
        return new self(set: $set);
    }
}
