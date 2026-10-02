<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Auth\Contracts\Permissions;
use Expansa\Auth\Contracts\Policy;
use Expansa\Patterns\Facade;

/**
 * Auth facade: permission and policy checks of Expansa\Auth\Manager.
 *
 * @method static void configure(?Permissions $permissions = null, array $policies = [])
 * @method static \Expansa\Auth\Manager setPolicy(string $class, Policy|string $policy)
 * @method static bool allows(?object $subject, string $permission)
 * @method static bool denies(?object $subject, string $permission)
 * @method static bool can(?object $subject, string $ability, object $resource)
 * @method static bool cannot(?object $subject, string $ability, object $resource)
 * @method static void authorize(?object $subject, string $ability, ?object $resource = null)
 */
class Auth extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Auth\Manager::class;
    }
}
