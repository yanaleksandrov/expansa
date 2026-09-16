<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use Expansa\Database\FieldEavTyped;
use Expansa\Support\Str;

/**
 * Gives a Model a typed meta storage (see {@see FieldEavTyped}) - an alternative to
 * {@see HasFieldEav}, not additive, for fields that need indexed filtering, sorting, or
 * full-text search by value, which a plain "{table}_fields" MEDIUMTEXT column can't do
 * efficiently or correctly. A using class must also declare `implements`
 * {@see \Expansa\Database\Contracts\Fieldable} - getId()/getFieldColumn() below satisfy it,
 * getTable() is already on every Model regardless.
 *
 * Both traits expose the same `field()` accessor (backing `$model->field`), so a model uses
 * one or the other, never both - `use`ing both on the same model is a PHP trait-collision
 * fatal error at class-declaration time, not a runtime surprise. getId()/getFieldColumn() are
 * duplicated here and in HasFieldEav rather than factored into a shared trait: since the two
 * are already mutually exclusive, only one copy is ever actually loaded on a given model, and
 * a shared trait would exist solely to be `use`d by exactly one of two callers.
 */
trait HasFieldEavTyped
{
    /**
     * @var array<class-string, string>
     */
    private static array $fieldColumnCache = [];

    /**
     * Primary key of this record, or 0 if it hasn't been persisted yet.
     */
    public function getId(): int
    {
        return (int) ($this->id ?? 0);
    }

    /**
     * Foreign key column name other tables use to reference this model — cached per class since
     * it never changes for a given table, only computed (Str::singularize()) on the first call.
     */
    public function getFieldColumn(): string
    {
        return self::$fieldColumnCache[static::class] ??= Str::singularize($this->getTable()) . '_id';
    }

    /**
     * Lazily resolves the typed per-row meta storage (see FieldEavTyped) - the typed
     * alternative to {@see HasFieldEav::field()}, not a second property alongside it.
     */
    protected function field(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value instanceof FieldEavTyped ? $value : new FieldEavTyped($this)
        );
    }
}
