<?php

declare(strict_types=1);

namespace App\User;

use InvalidArgumentException;

/**
 * Base class used to implement the API for user roles and their capabilities.
 *
 * Roles are keyed by name, each holding a display name and a plain list of
 * granted capability names. Example:
 * [
 *    'editor' => [
 *       'name'         => 'Editor',
 *       'capabilities' => ['read', 'types_edit'],
 *    ],
 * ]
 */
class Roles
{
    /**
     * Registered roles, keyed by role name.
     *
     * @var array<string, array{name: string, capabilities: array<string>}>
     */
    private static array $roles = [];

    /**
     * Register a role with its list of capabilities. Does nothing if the
     * role already exists.
     *
     * @param string       $role         Role name (used as its unique key).
     * @param string       $displayName  Role display name.
     * @param string|array $capabilities Capability names, e.g. ['edit_posts', 'delete_posts'],
     *                                   or the name of an existing role to copy capabilities from.
     * @return bool True if the role was registered, false if it already existed.
     */
    public static function register(string $role, string $displayName, string|array $capabilities): bool
    {
        if (isset(self::$roles[$role])) {
            return false;
        }

        if (is_string($capabilities)) {
            if (! isset(self::$roles[$capabilities])) {
                throw new InvalidArgumentException(t('You are trying to copy capabilities from a non exists role.'));
            }
            $capabilities = self::$roles[$capabilities]['capabilities'];
        }

        self::$roles[$role] = [
            'name'         => $displayName,
            'capabilities' => $capabilities,
        ];

        return true;
    }

    /**
     * Retrieve a role by name, or every registered role when none is given.
     *
     * @param string $role
     * @return array Role data if found, an empty array if it doesn't exist
     *               (or the full role list when $role is omitted).
     */
    public static function get(string $role = ''): array
    {
        return $role === '' ? self::$roles : self::$roles[$role] ?? [];
    }

    /**
     * Remove a role.
     *
     * @param string $role
     * @return bool
     */
    public static function delete(string $role): bool
    {
        if (! isset(self::$roles[$role])) {
            return false;
        }

        unset(self::$roles[$role]);

        return true;
    }

    /**
     * Grant one or more capabilities to a role.
     *
     * @param string       $role
     * @param string|array $capability Single capability or a list of capabilities.
     * @return bool
     */
    public static function set(string $role, string|array $capability): bool
    {
        if (! isset(self::$roles[$role])) {
            throw new InvalidArgumentException(t('You are trying set capability for non exists role.'));
        }

        self::$roles[$role]['capabilities'] = array_values(array_unique([
            ...self::$roles[$role]['capabilities'],
            ...(array) $capability,
        ]));

        return true;
    }

    /**
     * Remove a capability from a role.
     *
     * @param string $role
     * @param string $capability
     * @return bool
     */
    public static function unset(string $role, string $capability): bool
    {
        if (! isset(self::$roles[$role])) {
            throw new InvalidArgumentException(t('You are trying unset capability for non exists role.'));
        }

        self::$roles[$role]['capabilities'] = array_values(
            array_diff(self::$roles[$role]['capabilities'], [$capability])
        );

        return true;
    }

    /**
     * Whether a role name is currently registered.
     *
     * @param string $role
     * @return bool
     */
    public static function exists(string $role): bool
    {
        return isset(self::$roles[$role]);
    }

    /**
     * Whether a role has any of the given capabilities.
     *
     * @param string       $role
     * @param string|array $capabilities Single capability or a list of capabilities.
     * @return bool
     */
    public static function hasCap(string $role, string|array $capabilities): bool
    {
        $granted = self::$roles[$role]['capabilities'] ?? [];
        if (! $granted) {
            return false;
        }

        return array_any((array) $capabilities, fn($capability) => in_array($capability, $granted, true));
    }
}
