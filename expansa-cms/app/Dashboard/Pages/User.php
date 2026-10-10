<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Api\User\Admin;
use App\Api\User\Events;
use App\Api\User\Sessions;
use App\Api\User\TwoFactor;
use App\Models\User as Account;
use Expansa\Facades\Access;
use Expansa\Facades\I18n;
use Expansa\Facades\Role;
use Expansa\Http\Exceptions\NotFound;
use Expansa\Http\Request;
use IntlDateFormatter;

/**
 * Account of a user for an administrator: status, devices, security log and actions.
 *
 * @package App\Dashboard
 */
final class User
{
    /**
     * Data of the `user` page for the account of `id`.
     *
     * @param Request $request
     * @return array<string, mixed>
     * @throws NotFound When there is no such account.
     */
    public static function data(Request $request): array
    {
        $user = Admin::find($request->getInt('id'));
        if ($user === null) {
            throw new NotFound(t('User not found'));
        }

        $admin    = Account::current();
        $date     = new IntlDateFormatter(I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
        $isActive = $user->status === Account::STATUS_ACTIVE;

        return [
            'user'           => $user,
            'roles'          => array_map(fn (string $role) => Role::get($role)['name'] ?? $role, $user->roles),
            'isActive'       => $isActive,
            'isSelf'         => $user->id === $admin?->id,
            'hasTwoFactor'   => TwoFactor::isEnabled($user),
            'canImpersonate' => $user->id !== $admin?->id && $isActive && ! Access::allows($user, 'users_edit'),
            'sessions'       => array_map(fn (array $session) => [
                ...$session,
                'active' => $date->format(strtotime($session['used_at'] ?: $session['created_at'])),
            ], Sessions::all($user)),
            'events'         => array_map(fn (array $event) => [
                ...$event,
                'label' => Events::label($event['event']),
                'date'  => $date->format(strtotime($event['created_at'])),
            ], Events::all($user, 50)),
        ];
    }
}
