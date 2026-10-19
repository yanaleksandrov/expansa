<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Api\User\Admin;
use App\Api\User\Events;
use App\Api\User\Sessions;
use App\Api\User\TwoFactor;
use App\Models\User;
use App\Support\Dates;
use Expansa\Access\Exceptions\AccessDenied;
use Expansa\Facades\Access;
use Expansa\Facades\Role;
use Expansa\Http\Exceptions\NotFound;
use Expansa\Http\Request;

/**
 * Profile page: the current user edits their own account, an administrator opens the account
 * of another user by `id` and also gets its status, devices, security log and actions, or fills
 * in a new account by `new`.
 *
 * @package App\Dashboard
 */
final class Profile
{
    /**
     * Data of the `profile` page: the account it edits.
     *
     * @param Request $request `id` of another account, the current one without it; `new` for a new one.
     * @return array<string, mixed>
     * @throws AccessDenied When the current user may not edit other accounts.
     * @throws NotFound     When there is no such account.
     */
    public static function data(Request $request): array
    {
        $isNew = isset($request->query['new']);
        $user  = self::user($request->getInt('id'), $isNew);

        return ['user' => $user, 'isSelf' => ! $isNew && $user->id === User::current()?->id, 'isNew' => $isNew];
    }

    /**
     * Get the account the profile edits: the current one for no `id` or its own; another one,
     * or a new unsaved one, only with the `users_edit` permission.
     *
     * @param int  $id
     * @param bool $isNew A new account, `id` is ignored.
     * @return User
     * @throws AccessDenied When the current user may not edit other accounts.
     * @throws NotFound     When there is no such account.
     */
    public static function user(int $id, bool $isNew = false): User
    {
        $current = User::current();
        if (! $isNew && ($id === 0 || $id === $current?->id)) {
            return $current ?? throw new AccessDenied('read');
        }

        if (! Access::allows($current, 'users_edit')) {
            throw new AccessDenied('users_edit');
        }

        if ($isNew) {
            return new User();
        }

        return Admin::find($id) ?? throw new NotFound(t('User not found'));
    }

    /**
     * Data of the administration of another account: status, devices, security log and actions.
     *
     * @param User $user
     * @return array<string, mixed>
     */
    public static function admin(User $user): array
    {
        $admin    = User::current();
        $isActive = $user->status === User::STATUS_ACTIVE;

        return [
            'user'           => $user,
            'roles'          => array_map(fn (string $role) => Role::get($role)['name'] ?? $role, $user->roles),
            'isActive'       => $isActive,
            'hasTwoFactor'   => TwoFactor::isEnabled($user),
            'canImpersonate' => $user->id !== $admin?->id && $isActive && ! Access::allows($user, 'users_edit'),
            'sessions'       => array_map(fn (array $session) => [
                ...$session,
                'active' => Dates::format($session['used_at'] ?: $session['created_at']),
            ], Sessions::all($user)),
            'events'         => array_map(fn (array $event) => [
                ...$event,
                'label' => Events::label($event['event']),
                'date'  => Dates::format($event['created_at']),
            ], Events::all($user, 50)),
        ];
    }
}
