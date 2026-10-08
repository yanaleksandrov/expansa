<?php

declare(strict_types=1);

namespace Expansa\Access\Contracts;

/**
 * Source of granted permissions: roles in the database, a config array, an external service.
 * Access only asks it, the storage and the meaning of the subject are up to the implementation.
 *
 * @package Expansa\Access
 */
interface Permissions
{
    /**
     * Check if the subject has a permission.
     *
     * @param object|null $subject    Who acts, null for a guest.
     * @param string      $permission Permission name, e.g. "article.update".
     * @return bool
     */
    public function has(?object $subject, string $permission): bool;
}
