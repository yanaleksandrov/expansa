<?php

declare(strict_types=1);

namespace Expansa\Security\Csrf\Contracts;

/**
 * Storage of CSRF tokens between requests: a cookie or the session.
 *
 * @package Expansa\Security
 */
interface Provider
{
    /**
     * Get the stored token, `null` if there is none.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string;

    public function set(string $key, string $token): void;
}
