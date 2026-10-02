<?php

declare(strict_types=1);

namespace Expansa\Auth\Contracts;

/**
 * Source of granted permissions: roles in the database, a config array, an external service.
 * Auth only asks it, the storage and the meaning of the subject are up to the implementation.
 *
 * @package Expansa\Auth
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
