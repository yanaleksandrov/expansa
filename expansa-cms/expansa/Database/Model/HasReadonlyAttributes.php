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
        return in_array($key, $this->readonly, true);
    }

    /**
     * Sets $key unless it's readonly and already has a value - a no-op returning false rather
     * than throwing, since a readonly violation here is expected (re-fill()ing an existing
     * model) rather than exceptional. Checks/writes through {@see Model\HasAttributes::$attributes}
     * (via getAttributes()/setAttribute()), not a same-named dynamic property: the latter would
     * silently miss every existing value, since isset() on an undeclared property is always false.
     *
     * @param string $key   Attribute name (already snake_case, same convention as isReadonly()).
     * @param mixed  $value Value to assign.
     * @return bool True if the value was assigned, false if the attribute is readonly and already set.
     */
    protected function setAttributeReadonly(string $key, mixed $value): bool
    {
        if ($this->isReadonly($key) && array_key_exists($key, $this->getAttributes())) {
            return false;
        }

        $this->setAttribute($key, $value);

        return true;
    }
}
