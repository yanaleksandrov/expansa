<?php

declare(strict_types=1);

namespace Expansa\Auth\Contracts;

use Expansa\Auth\Exceptions\Denied;
use Expansa\Auth\Exceptions\InvalidState;
use Expansa\Auth\Exceptions\InvalidToken;
use Expansa\Auth\Exceptions\RequestFailed;
use Expansa\Auth\OAuth\Profile;
use Expansa\Auth\OAuth\State;

/**
 * External sign-in provider (OAuth 2.0, OpenID Connect): sends the user to its consent page
 * and turns the callback into a verified profile. Keeps no state: the State lives in the
 * caller's session between the two steps.
 *
 * @package Expansa\Auth
 */
interface Provider
{
    /**
     * Configured name, e.g. `google`; State and Profile carry it.
     */
    public string $name { get; }

    /**
     * URL of the provider's consent page to redirect the user to.
     *
     * @param State $state Created for this provider and kept until the callback.
     * @return string
     */
    public function redirect(State $state): string;

    /**
     * Verify the callback and get the profile of the signed-in user.
     *
     * @param array<string, mixed> $query Callback query parameters.
     * @param State                $state The state of redirect().
     * @return Profile
     * @throws Denied        If the user or the provider refused the sign-in.
     * @throws InvalidState  If the state is foreign, expired or the callback is malformed.
     * @throws InvalidToken  If the identity token fails a check.
     * @throws RequestFailed If the provider can't be reached or answers with an error.
     */
    public function profile(array $query, State $state): Profile;
}
