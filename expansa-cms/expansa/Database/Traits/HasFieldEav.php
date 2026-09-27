<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

use Expansa\Database\Attribute;
use Expansa\Database\FieldEav;
use Expansa\Support\Str;

/**
 * Key/value meta fields of a model in its "{table}_fields" table, read as `$model->field`, see FieldEav.
 * The model also implements Contracts\Fieldable. Excludes HasFieldEavTyped: both declare field().
 *
 * @package Expansa\Database\Traits
 */
trait HasFieldEav
{
    /**
     * Field storage of this model, created on first `$model->field`.
     */
    private ?FieldEav $fieldEav = null;

    public function getId(): int
    {
        return (int) ($this->id ?? 0);
    }

    /**
     * Get the foreign key of the model, such as "user_id", cached per class.
     *
     * @return string
     */
    public function getFieldColumn(): string
    {
        static $cache = [];

        return $cache[static::class] ??= Str::singularize($this->table) . '_id';
    }

    protected function field(): Attribute
    {
        return Attribute::get(fn () => $this->fieldEav ??= new FieldEav($this));
    }
}
