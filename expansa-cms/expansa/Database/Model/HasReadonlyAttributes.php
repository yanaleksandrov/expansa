<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

/**
 * Trait HasReadOnly.
 *
 * Provides support for readonly attributes in a model. Readonly attributes can
 * be set once (typically during creation) but cannot be modified afterwards.
 *
 * The consuming class must declare its own `protected array $readonly = [...]`
 * — this trait deliberately does not declare that property itself. PHP treats
 * a class redeclaring a typed trait property with a different default value as
 * an incompatible declaration (a fatal error), and since every user of this
 * trait needs its own list of readonly attributes, the property has to live
 * solely on the consuming class.
 */
trait HasReadonlyAttributes
{
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
