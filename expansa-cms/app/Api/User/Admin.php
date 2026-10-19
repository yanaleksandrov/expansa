<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Facades\Access;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Facades\Role;
use Expansa\Facades\Session;

/**
 * What an administrator does with the account of another user: disable it, change its email, sign it
 * out everywhere, send a password reset, turn its two-factor authentication off, and sign in as the user to see the
 * site with their eyes. Every action is logged on the account; signing in as somebody keeps the
 * administrator's own account in the browser, one click brings it back. Only the owner manages the
 * owner's account.
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
     * Whether the account belongs to the owner and somebody else acts on it.
     *
     * @param User $admin
     * @param User $user
     * @return bool
     */
    public static function isProtected(User $admin, User $user): bool
    {
        return $user->id !== $admin->id && $user->hasRole(User::ROLE_OWNER);
    }

    /**
     * Roles an administrator may give: every registered one, the owner role only by an owner.
     *
     * @param User $admin
     * @return array<string, string> Names by role.
     */
    public static function assignableRoles(User $admin): array
    {
        $roles = array_map(static fn (array $role) => $role['name'], Role::all());

        return $admin->hasRole(User::ROLE_OWNER) ? $roles : array_diff_key($roles, [User::ROLE_OWNER => true]);
    }

    /**
     * Check a role an administrator gives to an account, a new one or a saved one.
     *
     * @param User      $admin
     * @param string    $role
     * @param User|null $user  Saved account, null for a new one.
     * @return string|null Why it is refused, null when it may be given.
     */
    public static function refuseRole(User $admin, string $role, ?User $user = null): ?string
    {
        if (! isset(self::assignableRoles($admin)[$role])) {
            return Role::hasRole($role) ? t('Only the owner can give the owner role.') : t('That role is not registered.');
        }

        // an administrator who takes away their own role may lose the way back to this page
        if ($user?->id === $admin->id && ! $admin->hasRole(User::ROLE_OWNER)) {
            return t('You cannot change your own role.');
        }

        $isLastOwner = $user?->hasRole(User::ROLE_OWNER) && $role !== User::ROLE_OWNER
            && Db::count('users', ['roles[~]' => '"' . User::ROLE_OWNER . '"']) <= 1;

        return $isLastOwner ? t('The site needs an owner: give the owner role to another user first.') : null;
    }

    /**
     * Give an account one role instead of its roles, after refuseRole().
     *
     * @param User   $admin
     * @param User   $user
     * @param string $role
     * @return void
     */
    public static function setRole(User $admin, User $user, string $role): void
    {
        $user->roles = [$role];
        $user->save();

        Events::record($user, 'role_changed', ['role' => $role, 'by' => $admin->login]);
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
            Db::delete('users_sessions', ['user_id' => $user->id]);
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
        $count = (int) (Db::delete('users_sessions', ['user_id' => $user->id])?->rowCount() ?? 0);
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
     * Change the email of an account at once, without a link to the new address; a change the user
     * started is dropped, its link stops working.
     *
     * @param User   $admin
     * @param User   $user
     * @param string $email New address, already checked to be free.
     * @return void
     */
    public static function changeEmail(User $admin, User $user, string $email): void
    {
        $changes = ['email' => $email];
        if ((string) $user->field->find(Verification::PENDING_EMAIL) !== '') {
            $changes += ['verification_token' => null, 'verification_token_expires_at' => null];
            $user->field->delete(Verification::PENDING_EMAIL);
        }

        $user->update($changes);
        Events::record($user, 'email_changed', ['by' => $admin->login]);
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
        Db::delete('users_two_factor', ['user_id' => $user->id]);
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
