<?php

declare(strict_types=1);

namespace Expansa\Auth;

use Expansa\Auth\Contracts\Permissions;
use Expansa\Auth\Contracts\Policy;
use Expansa\Auth\Exceptions\AccessDenied;
use Expansa\Auth\Exceptions\PolicyNotFound;
use InvalidArgumentException;

/**
 * Authorization: what a subject is allowed to do, the Auth facade instance.
 *
 * Permissions are global names ("article.update") answered by the configured source; abilities
 * ("update") are checked on a resource by the policy of its class. The subject is any object or
 * null for a guest: who it is and where its rights are stored is decided by the application.
 * Without configuration every check is denied.
 *
 * @package Expansa\Auth
 */
final class Manager
{
    /**
     * Source of permissions, null denies every permission.
     *
     * @var Permissions|null
     */
    public private(set) ?Permissions $permissions = null;

    /**
     * Policies by resource class, an instance or a class created on first use.
     *
     * @var array<class-string, Policy|class-string<Policy>>
     */
    private array $policies = [];

    /**
     * Policies found for resource classes, including the ones matched by a parent or an interface.
     *
     * @var array<class-string, Policy>
     */
    private array $resolved = [];

    /**
     * Set the permission source and the policies, previous configuration is dropped.
     *
     * @param Permissions|null                                $permissions
     * @param array<class-string, Policy|class-string<Policy>> $policies    Resource class => policy.
     * @return void
     */
    public function configure(?Permissions $permissions = null, array $policies = []): void
    {
        $this->permissions = $permissions;
        $this->policies    = $policies;
        $this->resolved    = [];
    }

    /**
     * Set the policy of a resource class, also used for its subclasses and implementations.
     *
     * @param class-string                $class  Resource class or interface.
     * @param Policy|class-string<Policy> $policy Instance, or a class without constructor arguments.
     * @return static
     */
    public function setPolicy(string $class, Policy|string $policy): static
    {
        $this->policies[$class] = $policy;
        $this->resolved         = [];

        return $this;
    }

    /**
     * Check if the subject has a permission.
     *
     * @param object|null $subject    Null for a guest.
     * @param string      $permission
     * @return bool
     */
    public function allows(?object $subject, string $permission): bool
    {
        return $this->permissions?->has($subject, $permission) ?? false;
    }

    /**
     * Check if the subject lacks a permission.
     *
     * @param object|null $subject    Null for a guest.
     * @param string      $permission
     * @return bool
     */
    public function denies(?object $subject, string $permission): bool
    {
        return ! $this->allows($subject, $permission);
    }

    /**
     * Check if the subject may perform an ability on the resource, by the policy of its class.
     *
     * @param object|null $subject  Null for a guest.
     * @param string      $ability
     * @param object      $resource
     * @return bool
     * @throws PolicyNotFound If no policy matches the resource class.
     */
    public function can(?object $subject, string $ability, object $resource): bool
    {
        return ($this->resolved[$resource::class] ?? $this->resolve($resource::class))->can($subject, $ability, $resource);
    }

    /**
     * Check if the subject may not perform an ability on the resource.
     *
     * @param object|null $subject  Null for a guest.
     * @param string      $ability
     * @param object      $resource
     * @return bool
     * @throws PolicyNotFound If no policy matches the resource class.
     */
    public function cannot(?object $subject, string $ability, object $resource): bool
    {
        return ! $this->can($subject, $ability, $resource);
    }

    /**
     * Throw if the check fails: a permission without a resource, an ability of its policy with one.
     *
     * @param object|null $subject  Null for a guest.
     * @param string      $ability  Permission or ability name.
     * @param object|null $resource
     * @return void
     * @throws AccessDenied   If the subject is not allowed.
     * @throws PolicyNotFound If no policy matches the resource class.
     */
    public function authorize(?object $subject, string $ability, ?object $resource = null): void
    {
        if ($resource === null ? ! $this->allows($subject, $ability) : ! $this->can($subject, $ability, $resource)) {
            throw new AccessDenied($ability, $resource);
        }
    }

    /**
     * Find the policy of a class: the class itself, then its parents from the nearest, then interfaces.
     *
     * @param class-string $class
     * @return Policy
     * @throws PolicyNotFound
     */
    private function resolve(string $class): Policy
    {
        // foreach is twice as fast as array_find() here
        foreach ([$class, ...class_parents($class), ...class_implements($class)] as $key) {
            if (isset($this->policies[$key])) {
                break;
            }
        }

        $policy = $this->policies[$key] ?? throw new PolicyNotFound("No policy is registered for [$class].");
        if (is_string($policy)) {
            if (! is_a($policy, Policy::class, true)) {
                throw new InvalidArgumentException("Policy [$policy] of [$key] does not implement " . Policy::class . '.');
            }

            $policy = $this->policies[$key] = new $policy();
        }

        return $this->resolved[$class] = $policy;
    }
}
