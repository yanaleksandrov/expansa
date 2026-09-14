<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use Expansa\Support\Str;

/**
 * Default {@see \Expansa\Database\Contracts\Fieldable} implementation for getId() and
 * getFieldColumn() — getTable() is already on every Model regardless, since a model can't
 * function without one. A model that wants Field support (User, Apikey, ...) uses this trait and
 * declares `implements Fieldable` itself; most models never need either, so neither lives on the
 * base Model class.
 */
trait HasFieldColumn
{
    /**
     * @var array<class-string, string>
     */
    private static array $fieldColumnCache = [];

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
        return self::$fieldColumnCache[static::class] ??= Str::singularize($this->getTable()) . '_id';
    }
}
