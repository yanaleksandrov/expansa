<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Facades\Auth;
use Expansa\Facades\Hook;
use Expansa\Facades\Safe;
use Expansa\Facades\Session;
use Expansa\Support\Error;
use Expansa\Support\Is;

/**
 * The end of every sign-in, whatever proved the user: a password, a passkey, a provider.
 * Refuses disabled and unconfirmed accounts, signs in, logs the event, warns the owner
 * about a new device and sends them back to the page they came for.
 */
final class SignIn
{
    /**
     * Hash of a random password with the default cost, verified when the login is unknown,
     * so the answer takes as long as for a wrong password and reveals nothing.
     */
    private const string DUMMY_HASH = '$2y$12$WSUJqf7Jrby7LYA0yT6On.NYf5cfGDJBRtro8cfmukrmEFGmfV8M2';

    /**
     * Check a login (or email) and password with throttling.
     *
     * @param array<string, mixed> $input `login`, `password`.
     * @return User|Error The user whose password matched; nothing is signed in yet.
     */
    public static function password(array $input): User|Error
    {
        $loginOrEmail = Safe::login($input['login'] ?? '');
        $password     = Safe::trim($input['password'] ?? '');

        $user = User::find($loginOrEmail, Is::email($loginOrEmail) ? 'email' : 'login');
        $user = $user instanceof User ? $user : null;

        try {
            $isValid = Auth::attempt(
                $user?->identifier ?? $loginOrEmail,
                (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                fn () => password_verify($password, $user?->password ?? self::DUMMY_HASH) && $user !== null,
            );
        } catch (TooManyAttempts $e) {
            if ($user !== null) {
                Events::record($user, 'sign_in_locked');
            }

            $minutes = (int) ceil($e->retryAfter / 60);

            return error('user-login', t('Too many sign-in attempts. Try again in :minutes min.', $minutes));
        }

        // the same message for both cases: telling them apart would reveal registered logins
        if (! $isValid) {
            if ($user !== null) {
                Events::record($user, 'sign_in_failed');
            }

            return error('user-login', t('These credentials do not match our records.'));
        }

        return self::refusal($user) ?? $user;
    }

    /**
     * Why a proven user may not sign in: a disabled account, or an email not confirmed yet,
     * in which case a new confirmation link is sent (once in a few minutes).
     *
     * @param User $user
     * @return Error|null
     */
    public static function refusal(User $user): ?Error
    {
        if ($user->status !== User::STATUS_ACTIVE) {
            return error('user-login', t('This account is disabled.'));
        }

        // only accounts that signed up themselves wait for a confirmation, created ones are trusted
        if (! $user->isVerified && $user->verificationToken !== null) {
            Verification::send($user);

            return error('user-login', t('Confirm your email first: we have sent you a link.'));
        }

        return null;
    }

    /**
     * Sign in a proven user.
     *
     * @param User   $user
     * @param bool   $remember
     * @param string $method     `password`, `passkey`, a provider name.
     * @param string $redirectTo Page the user came for, see target().
     * @return string URL to go to.
     */
    public static function finish(User $user, bool $remember, string $method, string $redirectTo = ''): string
    {
        $isNewDevice = ! Auth::isTrustedDevice($user->identifier);

        Auth::login($user, $remember);
        if (Session::isStarted()) {
            Session::regenerateId();
        }

        Events::record($user, 'sign_in', ['method' => $method]);

        if ($isNewDevice) {
            Security::notifyNewDevice($user, $method);
        }

        return self::target($redirectTo);
    }

    /**
     * The page to go to after signing in: a path of this site the user came for, the dashboard otherwise.
     * An absolute or protocol-relative URL is refused, so a link can't send the user to another site.
     *
     * @param string $redirectTo
     * @return string
     */
    public static function target(string $redirectTo): string
    {
        $path = trim($redirectTo);

        $isLocal = str_starts_with($path, '/')
            && ! str_starts_with($path, '//')
            && ! str_contains($path, '\\')
            && ! preg_match('/[\x00-\x1f]/', $path)
            && ! preg_match('~(^|/)(sign-in|sign-up|sign-out|reset-password)(/|\?|$)~', $path);

        return (string) Hook::call('signInRedirect', $isLocal ? url(ltrim($path, '/')) : url('dashboard'), $redirectTo);
    }
}
