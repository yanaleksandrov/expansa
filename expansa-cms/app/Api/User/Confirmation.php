<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Facades\Auth;
use Expansa\Facades\Session;
use Throwable;

/**
 * "Confirm it's you" before a security change: a stolen auth cookie alone must not be enough to add
 * a sign-in method, change the email or sign the owner out of their devices. The password or a passkey
 * of the account confirms the session for TTL seconds, so several changes in a row ask only once.
 */
final class Confirmation
{
    /**
     * Seconds a confirmation lasts.
     */
    public const int TTL = 900;

    /**
     * Session key of the confirmation: user ID and expiry.
     */
    private const string SESSION_KEY = 'auth.confirmed';

    /**
     * Wrong passwords allowed per account in the window of TTL seconds.
     */
    private const int MAX_ATTEMPTS = 10;

    /**
     * Whether the session of the user is confirmed: recently, or now by the `password` of the request.
     *
     * @param User                 $user
     * @param array<string, mixed> $input May carry the current `password`.
     * @return bool
     */
    public static function check(User $user, array $input = []): bool
    {
        if (self::isConfirmed($user)) {
            return true;
        }

        $password = trim((string) ($input['password'] ?? ''));

        return $password !== '' && self::confirm($user, $password);
    }

    /**
     * Whether the session of the user was confirmed within TTL.
     *
     * @param User $user
     * @return bool
     */
    public static function isConfirmed(User $user): bool
    {
        self::startSession();
        $confirmed = Session::get(self::SESSION_KEY);

        return is_array($confirmed) && ($confirmed['user'] ?? 0) === $user->id && ($confirmed['until'] ?? 0) > time();
    }

    /**
     * Confirm by the current password.
     *
     * @param User   $user
     * @param string $password
     * @return bool
     */
    public static function confirm(User $user, string $password): bool
    {
        Auth::limit("confirm:$user->id", self::MAX_ATTEMPTS, self::TTL);

        return password_verify($password, $user->password) && self::remember($user);
    }

    /**
     * Confirm by a passkey of the account: for owners without a known password, e.g. signed up through a provider.
     *
     * @param User   $user
     * @param string $json Browser credential of the request options of Passkey.
     * @return bool
     */
    public static function confirmWithPasskey(User $user, string $json): bool
    {
        try {
            $owner = new Passkey()->verify($json);
        } catch (Throwable) {
            return false;
        }

        return $owner->id === $user->id && self::remember($user);
    }

    /**
     * Start the session of the request if needed.
     *
     * @return void
     */
    private static function startSession(): void
    {
        if (! Session::isStarted()) {
            Session::start();
        }
    }

    /**
     * Mark the session confirmed for TTL seconds.
     *
     * @param User $user
     * @return true
     */
    private static function remember(User $user): bool
    {
        self::startSession();
        Session::set(self::SESSION_KEY, ['user' => $user->id, 'until' => time() + self::TTL]);

        return true;
    }
}
