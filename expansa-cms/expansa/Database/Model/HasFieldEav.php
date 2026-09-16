<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use Expansa\Database\FieldEav;
use Expansa\Support\Str;

/**
 * Gives a Model a dynamic per-row meta storage (see {@see FieldEav}) for arbitrary fields — one
 * owner can have many rows in its own "{table}_fields" table, one per key. A using class must
 * also declare `implements` {@see \Expansa\Database\Contracts\Fieldable} - getId()/getFieldColumn()
 * below satisfy it, getTable() is already on every Model regardless - FieldEav only ever talks to
 * its owner through that contract, never a concrete Model.
 *
 * Mutually exclusive with {@see HasFieldEavTyped}: both expose the exact same `field()` accessor
 * (backing the model's `$model->field` property), one storage or the other, never both. Using
 * both traits on one model is a PHP trait-collision fatal error, not a runtime surprise - pick
 * whichever one actually fits before wiring it up, there's no in-between. getId()/getFieldColumn()
 * are duplicated here and in HasFieldEavTyped rather than factored into a shared trait: since the two
 * are already mutually exclusive, only one copy is ever actually loaded on a given model, and a
 * shared trait would exist solely to be `use`d by exactly one of two callers.
 */
trait HasFieldEav
{
    /**
     * The FieldEav instance backing $model->field, memoized per model instance - see field() below.
     *
     * @var ?FieldEav
     */
    private ?FieldEav $fieldEavInstance = null;

    /**
     * Primary key of this record, or 0 if it hasn't been persisted yet.
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) ($this->id ?? 0);
    }

    /**
     * Foreign key column name other tables use to reference this model — cached per class since
     * it never changes for a given table, only computed (Str::singularize()) on the first call.
     *
     * @return string
     */
    public function getFieldColumn(): string
    {
        // Scoped to this method only - no other method reads or resets this cache.
        static $cache = [];

        return $cache[static::class] ??= Str::singularize($this->getTable()) . '_id';
    }

    /**
     * Lazily resolves the dynamic per-row meta storage for arbitrary, non-structural fields
     * (see FieldEav) — not the same thing as roles(). Memoized on $fieldEavInstance, not via the
     * $value the Attribute mutator receives: getAttribute() never writes a get closure's return
     * value back into $attributes, so `$value instanceof FieldEav` was never actually true and a
     * fresh FieldEav (and a fresh copy of its own memoized $fieldsForeignTable/$fieldsForeignKey)
     * was allocated on every single `$model->field` access, e.g. once per iteration of
     * `foreach ($rows as $row) { $row->field->add(...); }`.
     *
     * @return Attribute
     */
    protected function field(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->fieldEavInstance ??= new FieldEav($this)
        );
    }
}
