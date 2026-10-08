<?php

declare(strict_types=1);

namespace Expansa\Access;

use Expansa\Access\Contracts\Permissions;
use Expansa\Access\Contracts\Subject;
use InvalidArgumentException;

/**
 * Roles with their permissions, the Role facade instance and a permission source of Manager.
 * A subject has a permission if any of its roles has it; the roles are kept in memory and
 * registered on every request, the roles of a subject are stored by the application.
 *
 * @package Expansa\Access
 */
final class Roles implements Permissions
{
    /**
     * Roles by name.
     *
     * @var array<string, array{name: string, permissions: string[]}>
     */
    private array $roles = [];

    /**
     * Add a role, does nothing if it exists.
     *
     * @param string          $role        Unique role name, e.g. "editor".
     * @param string          $name        Display name.
     * @param string[]|string $permissions Permission names, or the role to copy them from.
     * @return bool False if the role exists.
     * @throws InvalidArgumentException If the role to copy from does not exist.
     */
    public function add(string $role, string $name, array|string $permissions = []): bool
    {
        if (isset($this->roles[$role])) {
            return false;
        }

        if (is_string($permissions)) {
            $permissions = $this->roles[$permissions]['permissions']
                ?? throw new InvalidArgumentException("Role [$permissions] to copy permissions from does not exist.");
        }

        $this->roles[$role] = ['name' => $name, 'permissions' => array_values(array_unique($permissions))];

        return true;
    }

    /**
     * Get a role.
     *
     * @param string $role
     * @return array{name: string, permissions: string[]}|null
     */
    public function get(string $role): ?array
    {
        return $this->roles[$role] ?? null;
    }

    /**
     * Get all roles by name.
     *
     * @return array<string, array{name: string, permissions: string[]}>
     */
    public function all(): array
    {
        return $this->roles;
    }

    /**
     * Check if a role exists.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return isset($this->roles[$role]);
    }

    /**
     * Remove a role, subjects keep its name but get nothing from it.
     *
     * @param string $role
     * @return bool False if the role does not exist.
     */
    public function forget(string $role): bool
    {
        if (! isset($this->roles[$role])) {
            return false;
        }

        unset($this->roles[$role]);

        return true;
    }

    /**
     * Add permissions to a role.
     *
     * @param string          $role
     * @param string[]|string $permissions
     * @return static
     * @throws InvalidArgumentException If the role does not exist.
     */
    public function grant(string $role, array|string $permissions): static
    {
        $current = $this->roles[$role]['permissions'] ?? throw new InvalidArgumentException("Role [$role] does not exist.");

        $this->roles[$role]['permissions'] = array_values(array_unique([...$current, ...(array) $permissions]));

        return $this;
    }

    /**
     * Take permissions from a role.
     *
     * @param string          $role
     * @param string[]|string $permissions
     * @return static
     * @throws InvalidArgumentException If the role does not exist.
     */
    public function revoke(string $role, array|string $permissions): static
    {
        $current = $this->roles[$role]['permissions'] ?? throw new InvalidArgumentException("Role [$role] does not exist.");

        $this->roles[$role]['permissions'] = array_values(array_diff($current, (array) $permissions));

        return $this;
    }

    /**
     * Check if a role has a permission.
     *
     * @param string $role
     * @param string $permission
     * @return bool False for an unknown role.
     */
    public function hasPermission(string $role, string $permission): bool
    {
        return in_array($permission, $this->roles[$role]['permissions'] ?? [], true);
    }

    /**
     * Check if any role of the subject has the permission; other subjects and guests have none.
     *
     * @param object|null $subject
     * @param string      $permission
     * @return bool
     */
    public function has(?object $subject, string $permission): bool
    {
        if (! $subject instanceof Subject) {
            return false;
        }

        foreach ($subject->roles as $role) {
            if (in_array($permission, $this->roles[$role]['permissions'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }
}
