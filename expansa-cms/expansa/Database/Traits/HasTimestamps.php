<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

use DateTime;
use Expansa\Database\Attribute;

/**
 * Reads `created_at` and `updated_at` as DateTime; a model method of the same name overrides the trait one.
 *
 * @package Expansa\Database\Traits
 */
trait HasTimestamps
{
    /**
     * Column the creation time is stamped in.
     */
    protected string $createdAtColumn = 'created_at';

    /**
     * Column the last update time is stamped in.
     */
    protected string $updatedAtColumn = 'updated_at';

    /**
     * Whether the model tracks timestamps; not read yet, an override point for an opt-out.
     */
    protected bool $timestamps = true;

    protected function createdAt(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? new DateTime($value) : null);
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? new DateTime($value) : null);
    }
}
