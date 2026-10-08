<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\Option;
use App\Models\User;
use Expansa\Auth\OAuth\Profile;
use Expansa\Auth\OAuth\State;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;
use Expansa\Facades\Session;
use Expansa\Support\Error;
use Expansa\Support\Hash;
use Throwable;

/**
 * Accounts of users at sign-in providers, the `user_identities` table: the provider and its
 * user ID, never its tokens. An account with the same email is never linked automatically:
 * whoever controls a provider account must not take over a local one by its email.
 */
final class Identities
{
    /**
     * Session key of the pending attempt.
     */
    public const string SESSION_KEY = 'oauth';

    /**
     * Display names of the built-in providers, others are capitalized.
     */
    private const array LABELS = ['google' => 'Google', 'github' => 'GitHub'];

    /**
     * Start a sign-in or connecting a provider: keep a new State in the session.
     *
     * @param string $provider   Configured name.
     * @param bool   $link       Connect to the signed-in user instead of signing in.
     * @param string $redirectTo Page to go to after signing in, see SignIn::target().
     * @return string URL of the consent page.
     * @throws Throwable If the IP starts too often, the provider is not configured or its discovery fails.
     */
    public static function start(string $provider, bool $link = false, string $redirectTo = ''): string
    {
        Auth::limit('oauth:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 20, 300);

        $state = State::create($provider);
        $url   = Auth::provider($provider)->redirect($state);

        if (! Session::isStarted()) {
            Session::start();
        }

        Session::set(self::SESSION_KEY, $state->toArray() + ['link' => $link, 'redirect_to' => $redirectTo]);

        return $url;
    }

    /**
     * Display name of a provider.
     *
     * @param string $provider
     * @return string
     */
    public static function label(string $provider): string
    {
        return self::LABELS[$provider] ?? ucfirst($provider);
    }

    /**
     * Find the user a provider profile signs in, by these rules:
     * a linked account signs in its owner; a signed-in user gets the account linked;
     * an email of an existing user is refused; otherwise a user is registered if sign-up is open.
     *
     * @param Profile   $profile Verified by the provider.
     * @param User|null $current The signed-in user, null for a guest.
     * @return User|Error Error codes: `oauth-linked`, `oauth-email`, `oauth-closed`, `oauth-failed`.
     */
    public static function resolve(Profile $profile, ?User $current): User|Error
    {
        $row = Db::get('user_identities', ['user_id'], ['provider' => $profile->provider, 'subject' => $profile->id]);

        if (is_array($row)) {
            $owner = User::find((int) $row['user_id']);

            return match (true) {
                ! $owner instanceof User                         => error('oauth-failed', t('The linked account was not found.')),
                $current !== null && $current->id !== $owner->id => error('oauth-linked', t('Connected to another user.')),
                default                                          => $owner,
            };
        }

        if ($current !== null) {
            return self::link($current, $profile) ? $current : error('oauth-failed', t('Could not connect the account.'));
        }

        if ($profile->email !== null && User::find($profile->email, 'email') instanceof User) {
            return error('oauth-email', t('An account with this email already exists.'));
        }

        if (! Option::get('users.membership') || $profile->email === null || ! $profile->emailVerified) {
            return error('oauth-closed', t('Registration is closed or the provider did not confirm your email.'));
        }

        $user = User::create([
            'login'       => self::login($profile),
            'email'       => $profile->email,
            'showname'    => $profile->name,
            'password'    => Hash::generate(32),
            'is_verified' => true,
        ]);

        if (! $user instanceof User || ! self::link($user, $profile)) {
            return error('oauth-failed', t('Could not create the account.'));
        }

        return $user;
    }

    /**
     * Get the connected accounts of a user for the profile.
     *
     * @param User $user
     * @return array<int, array{id: int, provider: string, email: string, created_at: string}>
     */
    public static function all(User $user): array
    {
        return Db::select('user_identities', ['id [Int]', 'provider', 'email', 'created_at'], [
            'user_id' => $user->id,
            'ORDER'   => ['provider' => 'ASC'],
        ]) ?? [];
    }

    /**
     * Disconnect an account of a user.
     *
     * @param User $user Owner, a foreign ID removes nothing.
     * @param int  $id
     * @return bool Whether the account was disconnected.
     */
    public static function delete(User $user, int $id): bool
    {
        return (Db::delete('user_identities', ['id' => $id, 'user_id' => $user->id])?->rowCount() ?? 0) > 0;
    }

    /**
     * Link a provider account to a user.
     *
     * @param User    $user
     * @param Profile $profile
     * @return bool
     */
    private static function link(User $user, Profile $profile): bool
    {
        return (Db::insert('user_identities', [
            'user_id'  => $user->id,
            'provider' => $profile->provider,
            'subject'  => $profile->id,
            'email'    => $profile->emailVerified ? (string) $profile->email : '',
        ])?->rowCount() ?? 0) > 0;
    }

    /**
     * Free login for a new user: the email name with a number when it is taken.
     *
     * @param Profile $profile
     * @return string
     */
    private static function login(Profile $profile): string
    {
        $base = Safe::login(strstr((string) $profile->email, '@', true) ?: $profile->name);
        if (mb_strlen($base) < 3) {
            $base .= ($base === '' ? '' : '-') . $profile->provider;
        }

        $login  = $base;
        $suffix = 1;
        while (User::find($login, 'login') instanceof User) {
            $login = $base . '-' . ++$suffix;
        }

        return $login;
    }
}
