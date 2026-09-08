<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use App\Models\TypedField;

/**
 * Gives a Model a second, typed meta storage (see {@see TypedField}) alongside HasFieldEav —
 * for fields that need indexed filtering, sorting, or full-text search by value, which a plain
 * "{table}_fields" MEDIUMTEXT column can't do efficiently or correctly. Additive, not a
 * replacement; also requires `public string $fieldsForeignKey`, shared with HasFieldEav.
 */
trait HasFieldTyped
{
    /**
     * Lazily resolves the typed per-row meta storage (see TypedField) — not field() (see
     * HasFieldEav), which stores everything as text in one table.
     */
    protected function typedField(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value instanceof TypedField ? $value : new TypedField($this)
        );
    }
}
