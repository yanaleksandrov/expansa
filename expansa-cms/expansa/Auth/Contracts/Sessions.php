<?php

declare(strict_types=1);

namespace Expansa\Auth\Contracts;

/**
 * Server-side records of sign-ins, so a single device can be signed out: the token carries
 * the session ID and stops working once its record is deleted. Only signed-in users get a record,
 * anonymous requests still write nothing. Listing the devices is up to the implementation.
 *
 * @package Expansa\Auth
 */
interface Sessions
{
    /**
     * Record a new sign-in.
     *
     * @param Identity $user
     * @param int      $expires Unix time the token expires at, the record may be dropped after it.
     * @return string Session ID: letters and digits only.
     */
    public function create(Identity $user, int $expires): string;

    /**
     * Check that the session exists and belongs to the user; may note when it was last used.
     *
     * @param string   $id
     * @param Identity $user
     * @return bool
     */
    public function isActive(string $id, Identity $user): bool;

    /**
     * Delete a session: its token stops working.
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id): void;
}
