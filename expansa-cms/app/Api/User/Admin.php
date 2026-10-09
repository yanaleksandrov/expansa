<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Facades\Access;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Facades\Session;

/**
 * What an administrator does with the account of another user: disable it, sign it out everywhere,
 * send a password reset, turn its two-factor authentication off, and sign in as the user to see the
 * site with their eyes. Every action is logged on the account; signing in as somebody keeps the
 * administrator's own account in the browser, one click brings it back.
 */
final class Admin
{
    /**
     * Session key of an administrator signed in as somebody: their ID and the ID of the user.
     */
    private const string IMPERSONATION_KEY = 'auth.impersonation';

    /**
     * Find a user an administrator may manage: not themselves for the actions that would lock them out.
     *
     * @param int $id
     * @return User|null
     */
    public static function find(int $id): ?User
    {
        $user = User::find($id);

        return $user instanceof User ? $user : null;
    }

    /**
     * Turn an account on or off; a disabled account loses its sessions at once.
     *
     * @param User $admin
     * @param User $user
     * @param bool $isActive
     * @return string|null Why it is refused, null when done.
     */
    public static function setActive(User $admin, User $user, bool $isActive): ?string
    {
        if ($user->id === $admin->id) {
            return t('You cannot disable your own account.');
        }

        $user->update(['status' => $isActive ? User::STATUS_ACTIVE : User::STATUS_INACTIVE]);
        if (! $isActive) {
            Db::delete('user_sessions', ['user_id' => $user->id]);
        }

        Events::record($user, $isActive ? 'account_enabled' : 'account_disabled', ['by' => $admin->login]);

        return null;
    }

    /**
     * Sign an account out of every device.
     *
     * @param User $admin
     * @param User $user
     * @return int Devices signed out.
     */
    public static function signOut(User $admin, User $user): int
    {
        $count = (int) (Db::delete('user_sessions', ['user_id' => $user->id])?->rowCount() ?? 0);
        Events::record($user, 'sessions_revoked', ['count' => $count, 'by' => $admin->login]);

        return $count;
    }

    /**
     * Send the account a password reset link.
     *
     * @param User $admin
     * @param User $user
     * @return void
     */
    public static function sendPasswordReset(User $admin, User $user): void
    {
        new UserService()->requestPasswordReset($user);
        Events::record($user, 'password_reset', ['by' => $admin->login]);
    }

    /**
     * Turn the two-factor authentication of an account off, e.g. when its owner lost the phone and the codes.
     *
     * @param User $admin
     * @param User $user
     * @return void
     */
    public static function disableTwoFactor(User $admin, User $user): void
    {
        Db::delete('user_two_factor', ['user_id' => $user->id]);
        Events::record($user, 'two_factor_disabled', ['by' => $admin->login]);
    }

    /**
     * Sign in as another user; the administrator stays in the browser as another account.
     * Other administrators can't be impersonated: that would grant nothing but hide who acted.
     *
     * @param User $admin
     * @param User $user
     * @return string|null Why it is refused, null when signed in as the user.
     */
    public static function impersonate(User $admin, User $user): ?string
    {
        if ($user->id === $admin->id) {
            return t('This is your own account.');
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            return t('This account is disabled.');
        }

        if (Access::allows($user, 'users_edit')) {
            return t('Other administrators cannot be impersonated.');
        }

        Auth::login($user, remember: false, trustDevice: false);
        self::startSession();
        Session::set(self::IMPERSONATION_KEY, ['admin' => $admin->id, 'user' => $user->id]);

        Events::record($admin, 'impersonation_started', ['user' => $user->login]);
        Events::record($user, 'impersonation_started', ['by' => $admin->login]);

        return null;
    }

    /**
     * The administrator signed in as the current user, null if nobody is impersonating.
     *
     * @return User|null
     */
    public static function getImpersonator(): ?User
    {
        $current = User::current();
        if ($current === null) {
            return null;
        }

        self::startSession();
        $impersonation = Session::get(self::IMPERSONATION_KEY);
        if (! is_array($impersonation) || ($impersonation['user'] ?? 0) !== $current->id) {
            return null;
        }

        return self::find((int) $impersonation['admin']);
    }

    /**
     * Stop impersonating: sign the user out and go back to the administrator's account.
     *
     * @return bool False if nobody was impersonating.
     */
    public static function stopImpersonating(): bool
    {
        $admin = self::getImpersonator();
        $user  = User::current();
        if ($admin === null || $user === null) {
            return false;
        }

        Session::forget(self::IMPERSONATION_KEY);
        Events::record($admin, 'impersonation_stopped', ['user' => $user->login]);

        // the session of the user ends, the administrator's account was kept in the browser
        Auth::logout();
        if (User::current()?->id !== $admin->id) {
            Auth::switchAccount($admin->identifier);
        }

        return User::current()?->id === $admin->id;
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
}
