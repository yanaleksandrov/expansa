<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

/**
 * Keeps sensitive attributes (passwords, tokens) out of toArray() and JSON; $attributes and saving stay complete.
 *
 * The model declares `protected array $hidden = [...]` itself: PHP rejects redeclaring
 * a typed trait property with another default.
 *
 * @package Expansa\Database\Traits
 */
trait HasHiddenAttributes
{
    protected function isHidden(string $key): bool
    {
        return in_array($key, $this->hidden, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_diff_key($this->attributes, array_flip($this->hidden));
    }
}
