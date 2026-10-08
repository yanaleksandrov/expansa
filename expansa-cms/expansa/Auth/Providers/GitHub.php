<?php

declare(strict_types=1);

namespace Expansa\Auth\Providers;

use Expansa\Auth\Exceptions\RequestFailed;
use Expansa\Auth\OAuth\Profile;
use Expansa\Auth\OAuth\State;

/**
 * GitHub OAuth app: the user from `/user`, the email from `/user/emails`, only a verified one.
 * The public email of the profile is not used: GitHub doesn't vouch for it.
 */
final class GitHub extends AbstractProvider
{
    protected const array SCOPES = ['read:user', 'user:email'];

    /**
     * REST API root.
     */
    private const string API = 'https://api.github.com';

    protected function getAuthorizeUrl(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function getTokenUrl(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function createProfile(array $token, State $state): Profile
    {
        $headers = [
            'Authorization' => 'Bearer ' . $token['access_token'],
            'Accept'        => 'application/vnd.github+json',
            'User-Agent'    => 'Expansa',
        ];

        $user = $this->request('GET', self::API . '/user', $headers);
        if (! is_int($user['id'] ?? null)) {
            throw new RequestFailed('The provider returned no user ID.');
        }

        $emails = array_filter($this->request('GET', self::API . '/user/emails', $headers), fn (mixed $email) => is_array($email)
            && is_string($email['email'] ?? null)
            && ($email['verified'] ?? false) === true);

        $email = array_find($emails, fn (array $email) => ($email['primary'] ?? false) === true) ?? reset($emails) ?: null;

        return new Profile(
            provider: $this->name,
            id: (string) $user['id'],
            email: $email['email'] ?? null,
            emailVerified: $email !== null,
            name: is_string($user['name'] ?? null) && $user['name'] !== '' ? $user['name'] : (string) ($user['login'] ?? ''),
            avatar: is_string($user['avatar_url'] ?? null) ? $user['avatar_url'] : null,
            raw: $user,
        );
    }
}
