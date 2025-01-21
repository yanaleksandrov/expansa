<?php

declare(strict_types=1);

namespace app\User;

use Expansa\Error;

/**
 * Base class used to implement the API for user roles and their capabilities.
 *
 * The role option is simple, the structure is organized by role name that store
 * the name in value of the 'name' key. The capabilities are stored as an array
 * in the value of the 'capabilities' key. Example:
 * [
 *    'rolename' => [
 *       'name'         => 'rolename',
 *       'capabilities' => []
 *    ]
 * ]
 */
class Roles
{
    /**
     * Object list of roles and capabilities.
     *
     * @var array[]
     */
    private static array $roles = [];

    /**
     * Add role name with list of capabilities.
     *
     * Updates the list of roles, if the role doesn't already exist.
     *
     * The capabilities are defined in the following format `array( 'read' => true );`
     * To explicitly deny a role a capability you set the value for that capability to false.
     *
     * @param string $display_name role display name
     * @param mixed  $capabilities List of capabilities keyed by the capability name, e.g. [ 'edit_posts', 'delete_posts' ].
     *                             You can specify the ID of an existing role as the value.
     *                             In this case, the capabilities of the specified role are copied to the new one.
     */
    public static function register(string $role, string $display_name, $capabilities)
    {
        $roles = self::fetch();
        if (empty($role) || isset($roles[$role])) {
            return new Error('roles-register', t('Sorry, the role with this ID already exists.'));
        }

        if (is_string($capabilities)) {
            if (isset($roles[$capabilities])) {
                $capabilities = $roles[$capabilities]['capabilities'];
            } else {
                return new Error('roles-register', t('You are trying to copy capabilities from a non exists role.'));
            }
        }

        self::$roles[$role] = [
            'name'         => $display_name,
            'capabilities' => $capabilities,
        ];

        return true;
    }

    /**
     * Retrieve role object by name.
     *
     * @param string $role
     * @return object role object if found, null if the role does not exist
     */
    public static function get(string $role)
    {
        $roles = self::fetch();
        if (isset($roles[$role])) {
            return $roles[$role];
        }

        return null;
    }

    /**
     * Remove role.
     *
     * @param string $role
     * @return bool
     */
    public static function delete(string $role): bool
    {
        $roles = self::fetch();
        if (! isset($roles[$role])) {
            return false;
        }

        unset(self::$roles[$role]);

        return true;
    }

    /**
     * Set capability to role.
     *
     * @param string $role
     * @param mixed $capability Single capability or capabilities array
     * @return bool|Error
     */
    public static function set(string $role, mixed $capability)
    {
        $roles = self::fetch();
        if (! isset($roles[$role])) {
            return new Error('roles-set', t('You are trying set capability for non exists role.'));
        }

        if (is_array($capability)) {
            self::$roles[$role]['capabilities'] = array_merge($roles[$role]['capabilities'], $capability);
        } else {
            self::$roles[$role]['capabilities'][] = $capability;
        }

        return true;
    }

    /**
     * Remove capability from role.
     *
     * @param string $role
     * @param string $capability
     * @return bool|Error
     */
    public static function unset(string $role, string $capability)
    {
        $roles = self::fetch();
        if (! isset($roles[$role])) {
            return new Error('roles-unset', t('You are trying unset capability for non exists role.'));
        }

        unset($roles[$role]['capabilities'][$capability]);

        return true;
    }

    /**
     * Whether role name is currently in the list of available roles.
     *
     * @param string $role
     * @return bool
     */
    public static function exists(string $role): bool
    {
        $roles = self::fetch();

        return isset($roles[$role]);
    }

    /**
     * Whether role name is currently in the list of available roles.
     *
     * @param string $role
     * @param $capabilities
     * @return bool
     */
    public static function has_cap(string $role, $capabilities): bool
    {
        $roles = self::fetch();
        $role  = $roles[$role] ?? [];
        if (empty($role)) {
            return false;
        }

        if (is_scalar($capabilities)) {
            $capabilities = [$capabilities];
        }

        return count(array_intersect($capabilities, $role['capabilities'])) > 0;
    }

    /**
     * Get all roles.
     */
    private static function fetch(): array
    {
        return self::$roles;
    }
}
