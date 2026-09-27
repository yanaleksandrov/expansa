<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

/**
 * Readonly attributes: set once, later writes through setAttribute() are ignored.
 *
 * The model declares `protected array $readonly = [...]` itself: PHP rejects redeclaring
 * a typed trait property with another default.
 *
 * @package Expansa\Database\Traits
 */
trait HasReadonlyAttributes
{
    protected function isReadonly(string $key): bool
    {
        return in_array($key, $this->readonly, true);
    }

    /**
     * Set an attribute unless it is readonly and already has a value.
     *
     * @param string $key   Attribute name in snake_case.
     * @param mixed  $value
     * @return bool False if the attribute was kept.
     */
    protected function setAttributeReadonly(string $key, mixed $value): bool
    {
        if ($this->isReadonly($key) && array_key_exists($key, $this->attributes)) {
            return false;
        }

        $this->setAttribute($key, $value);

        return true;
    }
}
