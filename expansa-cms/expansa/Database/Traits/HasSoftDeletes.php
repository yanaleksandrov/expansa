<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

/**
 * Soft deletes: delete() stamps the column instead of deleting the row, queries skip stamped rows.
 *
 * @package Expansa\Database\Traits
 */
trait HasSoftDeletes
{
    /**
     * Column the deletion time is stamped in.
     */
    public protected(set) string $deletedAtColumn = 'deleted_at';

    /**
     * Whether the model soft-deletes; not read yet, Query checks the trait, an override point for an opt-out.
     */
    protected bool $softDelete = true;
}
