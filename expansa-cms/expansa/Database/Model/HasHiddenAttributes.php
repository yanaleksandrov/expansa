<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

/**
 * Trait HasHiddenAttributes.
 *
 * Excludes sensitive attributes (passwords, tokens, ...) from a model's
 * array/JSON representation, without touching what's actually stored or sent
 * to the database — getAttributes() stays complete, only toArray() (and, by
 * extension, json_encode()) is filtered.
 *
 * The consuming class must declare its own `protected array $hidden = [...]`
 * — same reasoning as HasReadonlyAttributes: a class can't redeclare a typed
 * trait property with a different default value without PHP treating it as
 * an incompatible declaration.
 */
trait HasHiddenAttributes
{
    /**
     * Checks if an attribute is hidden from the array/JSON representation.
     *
     * @param string $key Attribute name.
     * @return bool
     */
    protected function isHidden(string $key): bool
    {
        return in_array($key, $this->hidden, true);
    }

    /**
     * Get the model's attributes with hidden ones removed.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_diff_key($this->getAttributes(), array_flip($this->hidden));
    }
}
