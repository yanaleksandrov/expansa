<?php

declare(strict_types=1);

namespace Expansa\Database\Contracts;

/**
 * Anything that can own a dynamic set of meta fields (see {@see \Expansa\Database\FieldEav}) —
 * currently every {@see \Expansa\Database\Model} subclass that `use`s {@see \Expansa\Database\Model\HasFieldEav}
 * or {@see \Expansa\Database\Model\HasFieldEavTyped} (both implement getId()/getFieldColumn() below)
 * and declares `implements Fieldable` itself (User, Term, Apikey, ...). App\Models\Post does NOT
 * implement this yet - it isn't even a Model - so passing one to FieldEav is a TypeError today.
 */
interface Fieldable
{
    /**
     * Primary key of the owning row, or 0 if it hasn't been persisted yet.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Name of the owning row's database table.
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Foreign key column name other tables use to reference this row, e.g. "user_id" for a
     * "users" row — this is what {@see \Expansa\Database\FieldEav} groups its own storage table by.
     *
     * @return string
     */
    public function getFieldColumn(): string;
}
