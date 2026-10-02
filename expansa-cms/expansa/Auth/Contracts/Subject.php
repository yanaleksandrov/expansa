<?php

declare(strict_types=1);

namespace Expansa\Auth\Contracts;

/**
 * Subject granted permissions through roles: a user, an API key, a service account.
 * Roles gives permissions only to subjects implementing it.
 *
 * @package Expansa\Auth
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
