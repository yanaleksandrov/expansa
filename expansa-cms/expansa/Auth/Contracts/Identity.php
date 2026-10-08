<?php

declare(strict_types=1);

namespace Expansa\Auth\Contracts;

/**
 * A user that can sign in: Auth puts its identifier into the token and signs it with its stamp.
 * Changing the stamp (a password hash, a security stamp) invalidates every token issued before.
 *
 * @package Expansa\Auth
 */
interface Identity
{
    /**
     * Stable unique name the user is found by: a login or a UUID.
     */
    public string $identifier { get; }

    /**
     * Secret that changes when every session must end, e.g. the password hash.
     */
    public string $stamp { get; }
}
