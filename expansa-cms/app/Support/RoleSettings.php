<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use Expansa\Facades\Role;

/**
 * Roles edited in the Settings: the `roles` option keeps the permissions of every role and the roles
 * added there; they replace the defaults of the code on every request. Built-in roles can't be deleted,
 * and the administrator always keeps the permissions that open the settings and the users, so nobody
 * locks themselves out.
 */
final class RoleSettings
{
    /**
     * Roles registered by the code, they can be changed but not deleted.
     */
    public const array BUILT_IN = ['admin', 'editor', 'author', 'subscriber'];

    /**
     * Permissions the administrator always keeps.
     */
    private const array ADMIN_PERMISSIONS = ['manage_options', 'users_edit'];

    /**
     * Permissions of the roles of the code, read before the saved roles replace them.
     *
     * @var string[]
     */
    private static array $known = [];

    /**
     * Apply the saved roles over the ones of the code; call after the code registered its roles.
     *
     * @return void
     */
    public static function apply(): void
    {
        self::$known = self::collect();

        foreach ((array) Option::get('roles', []) as $key => $role) {
            if (! is_array($role) || ! preg_match('/^[a-z0-9_-]+$/', (string) $key)) {
                continue;
            }

            $permissions = array_keys(array_filter((array) ($role['permissions'] ?? [])));
            $name        = (string) ($role['name'] ?? '') ?: (Role::get($key)['name'] ?? $key);

            Role::forget($key);
            if ($key === 'admin') {
                $permissions = array_values(array_unique([...$permissions, ...self::ADMIN_PERMISSIONS]));
            }

            Role::add($key, $name, $permissions);
        }
    }

    /**
     * Turn the submitted form into the option: drop deleted custom roles, add the new one,
     * keep the administrator's permissions.
     *
     * @param array<string, mixed> $roles Form values: `key => [name, permissions => [permission => on], delete]`,
     *                                    a new role under `__new` with its own `key`.
     * @return array<string, array{name: string, permissions: array<string, bool>}>
     */
    public static function normalize(array $roles): array
    {
        $new = (array) ($roles['__new'] ?? []);
        unset($roles['__new']);

        $key = strtolower(trim((string) ($new['key'] ?? '')));
        if ($key !== '' && preg_match('/^[a-z0-9_-]+$/', $key) && ! isset($roles[$key])) {
            $roles[$key] = ['name' => trim((string) ($new['name'] ?? '')) ?: $key, 'permissions' => []];
        }

        $result = [];
        foreach ($roles as $roleKey => $role) {
            $isDeleted = ! empty($role['delete']) && ! in_array($roleKey, self::BUILT_IN, true);
            if (! is_array($role) || $isDeleted) {
                continue;
            }

            // "_" is the hidden field that makes an empty list arrive at all
            $checked     = array_filter(
                (array) ($role['permissions'] ?? []),
                fn ($value, $permission) => $permission !== '_' && $value,
                ARRAY_FILTER_USE_BOTH,
            );
            $permissions = array_map(fn () => true, $checked);
            if ($roleKey === 'admin') {
                $permissions += array_fill_keys(self::ADMIN_PERMISSIONS, true);
            }

            $result[$roleKey] = ['name' => trim((string) ($role['name'] ?? '')), 'permissions' => $permissions];
        }

        return $result;
    }

    /**
     * Every permission a role may have: those of the registered roles.
     *
     * @return string[]
     */
    public static function getPermissions(): array
    {
        $permissions = array_values(array_unique([...self::$known, ...self::collect()]));
        sort($permissions);

        return $permissions;
    }

    /**
     * Permissions of the roles registered now.
     *
     * @return string[]
     */
    private static function collect(): array
    {
        return array_merge([], ...array_map(fn (array $role) => $role['permissions'], array_values(Role::all())));
    }
}
