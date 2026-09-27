<?php

declare(strict_types=1);

namespace Expansa\Database\Contracts;

/**
 * Owner of meta fields, see FieldEav: a model with Traits\HasFieldEav or Traits\HasFieldEavTyped.
 *
 * @package Expansa\Database\Contracts
 */
interface Fieldable
{
    /**
     * Table of the owner, the fields are in "{table}_fields".
     */
    public string $table { get; }

    /**
     * Get the primary key, 0 before the owner is saved.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Get the foreign key column of the fields table, such as "user_id".
     *
     * @return string
     */
    public function getFieldColumn(): string;
}
