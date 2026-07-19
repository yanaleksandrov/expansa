<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

/**
 * Trait HasReadOnly.
 *
 * Provides support for readonly attributes in a model. Readonly attributes can
 * be set once (typically during creation) but cannot be modified afterwards.
 */
trait HasReadonlyAttributes
{
    /**
     * List of readonly attributes. Can be set once, cannot be modified afterwards.
     *
     * @var array<string>
     */
    protected array $readonly = [];

    /**
     * Checks if an attribute is readonly.
     *
     * @param string $key Attribute name.
     *
     * @return bool True if the attribute is readonly, false otherwise
     */
    protected function isReadonly(string $key): bool
    {
        return in_array($key, $this->readonly);
    }

    /**
     * Sets a value for a readonly attribute.
     *
     * @param string $key Attribute name
     * @param mixed $value Value to assign.
     *
     * @return bool True if the value was assigned, false if the attribute is readonly and already set
     */
    protected function setAttributeReadonly(string $key, mixed $value): bool
    {
        if ($this->isReadonly($key) && isset($this->$key)) {
            return false;
        }

        $this->$key = $value;
        return true;
    }
}
