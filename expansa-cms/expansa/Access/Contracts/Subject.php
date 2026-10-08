<?php

declare(strict_types=1);

namespace Expansa\Access\Contracts;

/**
 * Subject granted permissions through roles: a user, an API key, a service account.
 * Roles gives permissions only to subjects implementing it.
 *
 * @package Expansa\Access
 */
interface Subject
{
    /**
     * Names of the roles assigned to the subject.
     *
     * @var string[]
     */
    public array $roles { get; }
}
