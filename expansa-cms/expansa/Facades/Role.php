<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Role facade: roles and their permissions of Expansa\Access\Roles.
 *
 * @method static bool  add(string $role, string $name, array|string $permissions = [])
 * @method static array|null get(string $role)
 * @method static array all()
 * @method static bool  hasRole(string $role)
 * @method static bool  forget(string $role)
 * @method static \Expansa\Access\Roles grant(string $role, array|string $permissions)
 * @method static \Expansa\Access\Roles revoke(string $role, array|string $permissions)
 * @method static bool  hasPermission(string $role, string $permission)
 * @method static bool  has(?object $subject, string $permission)
 */
class Role extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Access\Roles::class;
    }
}
