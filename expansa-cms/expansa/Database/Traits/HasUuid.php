<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

/**
 * UUID of a new row: save() fills the column with a UUIDv7 before the insert, unless it is set.
 * Generated in PHP, not by a database trigger: hosts without the SUPER privilege refuse triggers.
 *
 * @package Expansa\Database\Traits
 */
trait HasUuid
{
    /**
     * Column the UUID is kept in.
     */
    public protected(set) string $uuidColumn = 'uuid';
}
