<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use App\Models\Field;

/**
 * Gives a Model a dynamic per-row meta storage (see {@see Field}) for arbitrary fields — one
 * owner can have many rows in its own "{table}_fields" table, one per key. A using class must
 * also declare `public string $fieldsForeignKey`, e.g. `= 'user_id'` — the foreign key column
 * on the "many" side that points back to it.
 */
trait HasFieldEav
{
    /**
     * Lazily resolves the dynamic per-row meta storage for arbitrary, non-structural fields
     * (see Field) — not the same thing as roles().
     */
    protected function field(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value instanceof Field ? $value : new Field($this)
        );
    }
}
