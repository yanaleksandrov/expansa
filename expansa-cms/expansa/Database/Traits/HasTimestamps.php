<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

use DateTime;
use Expansa\Database\Attribute;

/**
 * Reads `created_at` and `updated_at` as DateTime; a model method of the same name overrides the trait one.
 * The database stamps both on insert, Query::save() stamps `updated_at` on an update that changes something.
 *
 * @package Expansa\Database\Traits
 */
trait HasTimestamps
{
    /**
     * Column the creation time is stamped in.
     */
    public protected(set) string $createdAtColumn = 'created_at';

    /**
     * Column the last update time is stamped in.
     */
    public protected(set) string $updatedAtColumn = 'updated_at';

    /**
     * Whether Query::save() stamps the update time; false for a model that sets it itself.
     */
    public protected(set) bool $timestamps = true;

    protected function createdAt(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? new DateTime($value) : null);
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? new DateTime($value) : null);
    }
}
